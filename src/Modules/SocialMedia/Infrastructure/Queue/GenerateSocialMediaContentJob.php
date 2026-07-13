<?php

declare(strict_types=1);

namespace Modules\SocialMedia\Infrastructure\Queue;

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
use Modules\SocialMedia\Application\DTOs\GenerateSocialMediaContentData;
use Modules\SocialMedia\Domain\Ports\SocialMediaContentGeneratorPort;
use Modules\SocialMedia\Domain\Ports\SocialMediaContentRepositoryPort;
use Modules\SocialMedia\Domain\Services\ContentQualityEvaluator;
use Modules\SocialMedia\Infrastructure\Broadcasting\SocialMediaAiGenerationProgress;
use Shared\Domain\Ports\AuditPort;
use Throwable;

/**
 * Runs the Step 2 quality-loop (max {@see ContentQualityEvaluator::MAX_ITERATIONS}
 * attempts, fresh Tavily research every iteration, auto-regeneration until all
 * 5 scores clear their threshold — or the best attempt is kept with a
 * `quality_warning` flag). Queued rather than synchronous (unlike Post's
 * AI-assist) because a full run can mean 5x (Tavily + AI + 6 images + 1
 * voiceover), well past what a single HTTP request should block on.
 *
 * Dependencies are method-injected on {@see self::handle()} (not constructor
 * promotion) — the job is serialized onto the Redis queue, so only plain
 * scalars/DTOs belong on `$this`.
 */
#[Queue('default')]
#[Tries(1)]
#[Timeout(300)]
final class GenerateSocialMediaContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $contentUuid,
        private readonly GenerateSocialMediaContentData $data,
        private readonly ?int $causerId = null,
    ) {}

    public function handle(
        SocialMediaContentGeneratorPort $generator,
        SocialMediaContentRepositoryPort $repository,
        ContentQualityEvaluator $evaluator,
        AuditPort $audit,
    ): void {
        $content = $repository->findByUuid($this->contentUuid);

        if ($content === null) {
            Log::warning('social_media.generation.content_missing', ['uuid' => $this->contentUuid]);

            return;
        }

        $causer = $this->causerId !== null ? User::find($this->causerId) : null;

        $bestAttempt = null;
        $bestOverallAverage = -1;
        $previousWeaknesses = [];
        $iterationsRan = 0;

        for ($iteration = 1; $iteration <= ContentQualityEvaluator::MAX_ITERATIONS; $iteration++) {
            $iterationsRan = $iteration;

            $this->broadcastIteration($causer, $iteration, 'iteration_start', "Starting iteration {$iteration}…");

            try {
                $attempt = $generator->generate($this->contentUuid, $this->data, $iteration, $previousWeaknesses, $causer);
            } catch (Throwable $e) {
                Log::warning('social_media.generation.iteration_failed', [
                    'uuid' => $this->contentUuid,
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
            DB::transaction(fn () => $repository->update($content, ['status' => 'needs_review', 'quality_warning' => true,
                'quality_warning_message' => 'Every iteration failed to generate — check provider/API logs.']));

            return;
        }

        $qualityWarning = ! $bestAttempt->scores->allScoresPass;

        DB::transaction(fn () => $repository->update($content, [
            'headline' => $bestAttempt->headline,
            'body' => $bestAttempt->body,
            'call_to_action' => $bestAttempt->callToAction,
            'hashtags' => $bestAttempt->hashtags,
            'platforms' => $bestAttempt->platforms,
            'cover_image_path' => $bestAttempt->coverImagePath,
            'cover_image_prompt' => $bestAttempt->coverImagePrompt,
            'scores' => $bestAttempt->scores->toArray(),
            'human_writing_index' => $bestAttempt->scores->humanWritingIndex->value,
            'virality_score' => $bestAttempt->scores->viralityScore->value,
            'engagement_score' => $bestAttempt->scores->engagementScore->value,
            'roi_score' => $bestAttempt->scores->roiScore->value,
            'trend_alignment' => $bestAttempt->scores->trendAlignment->value,
            'overall_score_avg' => $bestAttempt->scores->overallAverage,
            'all_scores_pass' => $bestAttempt->scores->allScoresPass,
            'iterations_required' => $iterationsRan,
            'quality_warning' => $qualityWarning,
            'quality_warning_message' => $qualityWarning
                ? 'Maximum iterations reached — showing the best attempt for manual review.'
                : null,
            'eeat_analysis' => $bestAttempt->eeatAnalysis,
            'optimization_suggestions' => $bestAttempt->optimizationSuggestions,
            'research_sources' => $bestAttempt->researchSources,
            'tavily_data_used' => $bestAttempt->tavilyDataUsed,
            'ai_detection_risk' => $bestAttempt->aiDetectionRisk,
            'status' => $qualityWarning ? 'needs_review' : 'ready',
        ]));

        $audit->log(
            event: 'social_media.ai.generation_completed',
            subject: $content,
            properties: [
                'iterations_required' => $iterationsRan,
                'all_scores_pass' => $bestAttempt->scores->allScoresPass,
                'overall_score_avg' => $bestAttempt->scores->overallAverage,
                'provider' => $this->data->provider,
            ],
            causer: $causer,
            logName: 'social_media',
        );

        $this->broadcastIteration(
            $causer,
            $iterationsRan,
            'completed',
            $qualityWarning ? 'Best attempt saved for review.' : 'Content ready — all scores passed.',
        );
    }

    private function broadcastIteration(?User $causer, int $iteration, string $stage, string $message): void
    {
        if ($causer === null) {
            return;
        }

        broadcast(new SocialMediaAiGenerationProgress(
            userId: (int) $causer->getAuthIdentifier(),
            contentUuid: $this->contentUuid,
            stage: $stage,
            message: $message,
            progress: (int) round(($iteration / ContentQualityEvaluator::MAX_ITERATIONS) * 100),
            iteration: $iteration,
        ));
    }
}
