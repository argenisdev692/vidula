<?php

declare(strict_types=1);

namespace Modules\Campaigns\Infrastructure\Queue;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Campaigns\Application\DTOs\GenerateCampaignData;
use Modules\Campaigns\Domain\Ports\CampaignGeneratorPort;
use Modules\Campaigns\Domain\Ports\CampaignRepositoryPort;
use Modules\Campaigns\Domain\Services\CampaignQualityEvaluator;
use Modules\Campaigns\Infrastructure\Broadcasting\CampaignAiGenerationProgress;
use Shared\Domain\Ports\AuditPort;
use Throwable;

/**
 * Runs the Step 2 quality-loop (max {@see CampaignQualityEvaluator::MAX_ITERATIONS}
 * attempts, fresh Tavily research every iteration, auto-regeneration until
 * all 5 scores clear their threshold — or the best attempt is kept with a
 * `quality_warning` flag). Queued rather than synchronous because a full run
 * can mean up to 5x (Tavily + AI + 3 images), well past what a single HTTP
 * request should block on.
 *
 * Dependencies are method-injected on {@see self::handle()} (not constructor
 * promotion) — the job is serialized onto the Redis queue, so only plain
 * scalars/DTOs belong on `$this`.
 */
#[Queue('default')]
#[Tries(1)]
#[Timeout(300)]
final class GenerateCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $campaignUuid,
        private readonly GenerateCampaignData $data,
        private readonly ?int $causerId = null,
    ) {}

    public function handle(
        CampaignGeneratorPort $generator,
        CampaignRepositoryPort $repository,
        CampaignQualityEvaluator $evaluator,
        AuditPort $audit,
    ): void {
        $campaign = $repository->findByUuid($this->campaignUuid);

        if ($campaign === null) {
            Log::warning('campaigns.generation.campaign_missing', ['uuid' => $this->campaignUuid]);

            return;
        }

        $causer = $this->causerId !== null ? User::find($this->causerId) : null;

        $bestAttempt = null;
        $bestOverallAverage = -1;
        $previousWeaknesses = [];
        $iterationsRan = 0;

        for ($iteration = 1; $iteration <= CampaignQualityEvaluator::MAX_ITERATIONS; $iteration++) {
            $iterationsRan = $iteration;

            $this->broadcastIteration($causer, $iteration, 'iteration_start', "Starting iteration {$iteration}…");

            try {
                $attempt = $generator->generate($this->campaignUuid, $this->data, $iteration, $previousWeaknesses, $causer);
            } catch (Throwable $e) {
                Log::warning('campaigns.generation.iteration_failed', [
                    'uuid' => $this->campaignUuid,
                    'iteration' => $iteration,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }

            if ($attempt->scores->overallAverage > $bestOverallAverage) {
                $bestOverallAverage = $attempt->scores->overallAverage;
                $bestAttempt = $attempt;
            }

            if ($attempt->scores->allScoresPass) {
                break;
            }

            $previousWeaknesses = $evaluator->identifyWeaknesses(
                $attempt->scores->toThresholdMap(),
                $attempt->scores->toExplanationMap(),
            );
        }

        if ($bestAttempt === null) {
            DB::transaction(fn () => $repository->update($campaign, ['status' => 'needs_review', 'quality_warning' => true,
                'quality_warning_message' => 'Every iteration failed to generate — check provider/API logs.']));

            return;
        }

        $qualityWarning = ! $bestAttempt->scores->allScoresPass;

        DB::transaction(fn () => $repository->update($campaign, [
            'headline' => $bestAttempt->headline,
            'primary_text' => $bestAttempt->primaryText,
            'description' => $bestAttempt->description,
            'call_to_action' => $bestAttempt->callToAction,
            'hashtags' => $bestAttempt->hashtags,
            'lead_form_questions' => $bestAttempt->leadFormQuestions,
            'targeting_suggestions' => $bestAttempt->targetingSuggestions,
            'platforms' => $bestAttempt->platforms,
            'cover_image_path' => $bestAttempt->coverImagePath,
            'cover_image_prompt' => $bestAttempt->coverImagePrompt,
            'scores' => $bestAttempt->scores->toArray(),
            'audience_fit_score' => $bestAttempt->scores->audienceFitScore->value,
            'virality_score' => $bestAttempt->scores->viralityScore->value,
            'roi_potential_score' => $bestAttempt->scores->roiPotentialScore->value,
            'lead_quality_score' => $bestAttempt->scores->leadQualityScore->value,
            'trend_relevance_score' => $bestAttempt->scores->trendRelevanceScore->value,
            'overall_score_avg' => $bestAttempt->scores->overallAverage,
            'success_probability_label' => $bestAttempt->scores->successProbabilityLabel,
            'all_scores_pass' => $bestAttempt->scores->allScoresPass,
            'iterations_required' => $iterationsRan,
            'quality_warning' => $qualityWarning,
            'quality_warning_message' => $qualityWarning
                ? 'Maximum iterations reached — showing the best attempt for manual review.'
                : null,
            'optimization_suggestions' => $bestAttempt->optimizationSuggestions,
            'research_sources' => $bestAttempt->researchSources,
            'tavily_data_used' => $bestAttempt->tavilyDataUsed,
            'ai_detection_risk' => $bestAttempt->aiDetectionRisk,
            'status' => $qualityWarning ? 'needs_review' : 'ready',
        ]));

        $audit->log(
            event: 'campaigns.ai.generation_completed',
            subject: $campaign,
            properties: [
                'iterations_required' => $iterationsRan,
                'all_scores_pass' => $bestAttempt->scores->allScoresPass,
                'overall_score_avg' => $bestAttempt->scores->overallAverage,
                'success_probability_label' => $bestAttempt->scores->successProbabilityLabel,
                'provider' => $this->data->provider,
            ],
            causer: $causer,
            logName: 'campaigns',
        );

        $this->broadcastIteration(
            $causer,
            $iterationsRan,
            'completed',
            $qualityWarning ? 'Best attempt saved for review.' : 'Campaign ready — all scores passed.',
        );
    }

    private function broadcastIteration(?User $causer, int $iteration, string $stage, string $message): void
    {
        if ($causer === null) {
            return;
        }

        broadcast(new CampaignAiGenerationProgress(
            userId: (int) $causer->getAuthIdentifier(),
            campaignUuid: $this->campaignUuid,
            stage: $stage,
            message: $message,
            progress: (int) round(($iteration / CampaignQualityEvaluator::MAX_ITERATIONS) * 100),
            iteration: $iteration,
        ));
    }
}
