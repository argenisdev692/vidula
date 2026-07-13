<?php

declare(strict_types=1);

namespace Modules\Campaigns\Infrastructure\Ai;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Campaigns\Application\DTOs\CampaignScoreResultData;
use Modules\Campaigns\Application\DTOs\CampaignScoreSetData;
use Modules\Campaigns\Application\DTOs\CampaignTopicIdeaData;
use Modules\Campaigns\Application\DTOs\GenerateCampaignData;
use Modules\Campaigns\Application\DTOs\GeneratedCampaignData;
use Modules\Campaigns\Application\DTOs\PlatformCampaignContentData;
use Modules\Campaigns\Application\DTOs\SuggestCampaignTopicsData;
use Modules\Campaigns\Domain\Ports\CampaignGeneratorPort;
use Modules\Campaigns\Domain\Ports\CampaignIdeatorPort;
use Modules\Campaigns\Domain\Services\CampaignQualityEvaluator;
use Modules\Campaigns\Infrastructure\Broadcasting\CampaignAiGenerationProgress;
use Modules\Campaigns\Infrastructure\Queue\GenerateCampaignJob;
use Modules\SocialMedia\Infrastructure\Ai\LaravelAiSocialMediaAssistantAdapter;
use Shared\Domain\Ports\StoragePort;
use Shared\Infrastructure\AI\AIClientInterface;
use Shared\Infrastructure\Branding\BrandPalette;
use Shared\Infrastructure\Company\CompanyProfile;
use Shared\Infrastructure\Research\TavilyClientInterface;

/**
 * Single adapter behind both Campaigns AI ports — mirrors
 * {@see LaravelAiSocialMediaAssistantAdapter}:
 * one class composing the agents so the research/prompt-assembly plumbing is
 * not duplicated, while each port stays small (ISP) for its own consumer.
 * This class performs exactly ONE generation attempt per {@see self::generate()}
 * call — the up-to-5-iteration quality loop is orchestrated by
 * {@see GenerateCampaignJob}, not here.
 *
 * Caching lives HERE, in the module adapter — never in the Shared AI/Tavily
 * clients (those stay pure transport + circuit breaker). `suggestTopics()`
 * always caches (no internal state). `generate()` caches ONLY the first
 * attempt (iteration 1, no previous weaknesses) — from iteration 2 onward
 * the quality loop deliberately targets specific failing scores, so those
 * attempts are never safe to reuse from cache.
 */
final readonly class LaravelAiCampaignAssistantAdapter implements CampaignGeneratorPort, CampaignIdeatorPort
{
    private const int CACHE_TTL_MINUTES = 15;

    public function __construct(
        private AIClientInterface $ai,
        private TavilyClientInterface $research,
        private StoragePort $storage,
    ) {}

    public function suggestTopics(SuggestCampaignTopicsData $data, ?object $causer = null): array
    {
        return Cache::remember(
            $this->cacheKey('suggest-topics', $data->toArray()),
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            function () use ($data): array {
                $company = CompanyProfile::data();
                $niche = $data->niche ?? $company['description'] ?? $company['name'];

                $research = $this->research->search([
                    "{$niche} Meta Ads lead generation trends 2026",
                    "{$niche} Facebook Instagram ad examples high ROI",
                    "{$niche} audience pain points buyers",
                ]);

                $prompt = implode("\n\n", array_filter([
                    "Niche: {$niche}",
                    $data->audience !== null ? "Target audience: {$data->audience}" : 'Target audience: infer from the niche.',
                    $data->businessGoal !== null ? "Business goal: {$data->businessGoal}" : null,
                    "Output language: {$data->language}",
                    'Web research context:'."\n".$this->formatResearch($research),
                    'Generate exactly 10 Meta Ads campaign angles as specified in your instructions.',
                ]));

                $response = $this->ai->generateStructured(SuggestCampaignTopicsAgent::class, $prompt, $data->provider);

                return array_map(
                    static fn (array $topic): CampaignTopicIdeaData => new CampaignTopicIdeaData(
                        title: (string) $topic['title'],
                        angle: (string) $topic['angle'],
                        hook: (string) $topic['hook'],
                        platform: (string) $topic['platform'],
                        estimatedVirality: (int) $topic['estimated_virality'],
                        estimatedEngagement: (string) $topic['estimated_engagement'],
                        estimatedRoi: (int) $topic['estimated_roi'],
                        estimatedLeadPotential: (int) $topic['estimated_lead_potential'],
                        difficulty: (string) $topic['difficulty'],
                        whyItWorks: (string) $topic['why_it_works'],
                        keyTrend: (string) $topic['key_trend'],
                        suggestedFormat: (string) $topic['suggested_format'],
                        contentType: (string) $topic['content_type'],
                        funnelStage: (string) $topic['funnel_stage'],
                    ),
                    (array) $response['campaign_topics'],
                );
            },
        );
    }

    public function generate(
        string $campaignUuid,
        GenerateCampaignData $data,
        int $iteration = 1,
        array $previousWeaknesses = [],
        ?object $causer = null,
    ): GeneratedCampaignData {
        $attempt = fn (): GeneratedCampaignData => $this->generateAttempt($campaignUuid, $data, $iteration, $previousWeaknesses, $causer);

        if ($iteration !== 1 || $previousWeaknesses !== []) {
            return $attempt();
        }

        return Cache::remember(
            $this->cacheKey('generate', $data->toArray()),
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            $attempt,
        );
    }

    /**
     * @param  list<array{score: string, current: int, target: int, gap: int, explanation: string}>  $previousWeaknesses
     */
    private function generateAttempt(
        string $campaignUuid,
        GenerateCampaignData $data,
        int $iteration,
        array $previousWeaknesses,
        ?object $causer,
    ): GeneratedCampaignData {
        $this->broadcast($causer, $campaignUuid, 'researching', "Iteration {$iteration}: researching fresh context…", 15, $iteration);

        $company = CompanyProfile::data();
        $research = $this->research->search($this->researchQueries($data, $iteration));
        $prompt = $this->buildContentPrompt($data, $company, $research, $iteration, $previousWeaknesses);

        $this->broadcast($causer, $campaignUuid, 'writing', "Iteration {$iteration}: writing the Meta Ads copy…", 45, $iteration);

        $response = $this->ai->generateStructured(GenerateCampaignAgent::class, $prompt, $data->provider);

        $this->broadcast($causer, $campaignUuid, 'scoring', "Iteration {$iteration}: scoring the attempt…", 70, $iteration);

        $scores = $this->mapScores((array) $response['scores']);

        $platforms = [];

        foreach ((array) $response['platforms'] as $platform => $variation) {
            /** @var array<string, mixed> $variation */
            $platforms[$platform] = $this->buildPlatformContent((string) $platform, $variation, $data);
        }

        $this->broadcast($causer, $campaignUuid, 'cover_image', "Iteration {$iteration}: generating the cover image…", 90, $iteration);

        /** @var array{title: string, visual: string} $coverConcept */
        $coverConcept = (array) $response['cover_image_concept'];
        $coverImagePath = $data->generateImages
            ? $this->generateAndStoreImage('cover', (string) $coverConcept['title'], (string) $coverConcept['visual'])
            : null;
        $coverImageUrl = $coverImagePath !== null ? $this->storage->publicUrl($coverImagePath) : null;

        $this->broadcast($causer, $campaignUuid, 'done', "Iteration {$iteration}: attempt ready.", 100, $iteration);

        /** @var array{content: mixed} $response */
        $content = (array) $response['content'];

        return new GeneratedCampaignData(
            headline: (string) $content['headline'],
            primaryText: (string) $content['primary_text'],
            description: isset($content['description']) ? (string) $content['description'] : null,
            callToAction: (string) $content['call_to_action'],
            hashtags: (array) $content['hashtags'],
            leadFormQuestions: (array) $content['lead_form_questions'],
            targetingSuggestions: (array) $content['targeting_suggestions'],
            platforms: $platforms,
            coverImagePath: $coverImagePath,
            coverImageUrl: $coverImageUrl,
            coverImagePrompt: "{$coverConcept['title']} — {$coverConcept['visual']}",
            scores: $scores,
            optimizationSuggestions: (array) $response['optimization_suggestions'],
            researchSources: (array) $response['research_sources'],
            tavilyDataUsed: (array) $response['tavily_data_used'],
            aiDetectionRisk: (array) $response['ai_detection_risk'],
            provider: $data->provider,
        );
    }

    /**
     * @param  array<string, mixed>  $variation
     */
    private function buildPlatformContent(string $platform, array $variation, GenerateCampaignData $data): PlatformCampaignContentData
    {
        /** @var array{title: string, visual: string} $imageConcept */
        $imageConcept = (array) $variation['image_concept'];

        $imagePath = $data->generateImages
            ? $this->generateAndStoreImage($platform, (string) $imageConcept['title'], (string) $imageConcept['visual'])
            : null;
        $imageUrl = $imagePath !== null ? $this->storage->publicUrl($imagePath) : null;

        return new PlatformCampaignContentData(
            platform: $platform,
            adaptedPrimaryText: (string) $variation['adapted_primary_text'],
            characterCount: (int) $variation['character_count'],
            headline: (string) $variation['headline'],
            description: isset($variation['description']) ? (string) $variation['description'] : null,
            hashtags: (array) $variation['hashtags'],
            imagePrompt: "{$imageConcept['title']} — {$imageConcept['visual']}",
            imagePath: $imagePath,
            imageUrl: $imageUrl,
        );
    }

    /**
     * @param  array<string, mixed>  $rawScores
     */
    private function mapScores(array $rawScores): CampaignScoreSetData
    {
        $toResult = function (string $key, array $raw): CampaignScoreResultData {
            $value = (int) $raw['value'];
            $threshold = CampaignQualityEvaluator::THRESHOLDS[$key];

            return new CampaignScoreResultData(
                value: $value,
                threshold: $threshold,
                passes: $value >= $threshold,
                factors: array_map(static fn (mixed $v): int => (int) $v, (array) $raw['factors']),
                explanation: (string) $raw['explanation'],
            );
        };

        $audienceFitScore = $toResult('audience_fit_score', (array) $rawScores['audience_fit_score']);
        $viralityScore = $toResult('virality_score', (array) $rawScores['virality_score']);
        $roiPotentialScore = $toResult('roi_potential_score', (array) $rawScores['roi_potential_score']);
        $leadQualityScore = $toResult('lead_quality_score', (array) $rawScores['lead_quality_score']);
        $trendRelevanceScore = $toResult('trend_relevance_score', (array) $rawScores['trend_relevance_score']);

        $evaluator = new CampaignQualityEvaluator;

        $evaluation = $evaluator->evaluate([
            'audience_fit_score' => $audienceFitScore->value,
            'virality_score' => $viralityScore->value,
            'roi_potential_score' => $roiPotentialScore->value,
            'lead_quality_score' => $leadQualityScore->value,
            'trend_relevance_score' => $trendRelevanceScore->value,
        ]);

        return new CampaignScoreSetData(
            audienceFitScore: $audienceFitScore,
            viralityScore: $viralityScore,
            roiPotentialScore: $roiPotentialScore,
            leadQualityScore: $leadQualityScore,
            trendRelevanceScore: $trendRelevanceScore,
            allScoresPass: $evaluation->allPass,
            overallAverage: $evaluation->overallAverage,
            successProbabilityLabel: $evaluator->successProbabilityLabel($evaluation->overallAverage),
        );
    }

    /**
     * Varies the research queries per iteration (Prompt2's convention) so a
     * retry gets genuinely fresh context instead of repeating the same search.
     *
     * @return list<string>
     */
    private function researchQueries(GenerateCampaignData $data, int $iteration): array
    {
        $niche = $data->niche ?? $data->topic;
        $base = array_filter([
            "{$data->topic} {$niche} Meta Ads 2026",
            $data->keyTrend !== null ? "{$data->keyTrend} statistics recent data" : null,
            "{$niche} lead generation audience insights",
        ]);

        return match (true) {
            $iteration >= 4 => [...$base, "{$niche} authority sources citations", "{$data->topic} best practices"],
            $iteration >= 2 => [...$base, "{$niche} viral ad examples Facebook Instagram", "{$data->topic} conversion rate benchmarks"],
            default => $base,
        };
    }

    /**
     * @param  array{name: string, description: ?string}  $company
     * @param  list<array{title: string, url: string, content: string, score: float}>  $research
     * @param  list<array{score: string, current: int, target: int, gap: int, explanation: string}>  $previousWeaknesses
     */
    private function buildContentPrompt(
        GenerateCampaignData $data,
        array $company,
        array $research,
        int $iteration,
        array $previousWeaknesses,
    ): string {
        return implode("\n\n", array_filter([
            "Company: {$company['name']}",
            $company['description'] !== null ? "Company description: {$company['description']}" : null,
            "Topic: {$data->topic}",
            $data->angle !== null ? "Angle: {$data->angle}" : null,
            $data->hook !== null ? "Hook: {$data->hook}" : null,
            $data->keyTrend !== null ? "Key trend to reference: {$data->keyTrend}" : null,
            $data->audience !== null ? "Target audience: {$data->audience}" : null,
            "Business goal: {$data->businessGoal}",
            "Brand voice: {$data->brandVoice}",
            "Funnel stage: {$data->funnelStage}",
            "Meta platform: {$data->platform}",
            "Ad format: {$data->adFormat}",
            "Output language: {$data->language}",
            'Web research context:'."\n".$this->formatResearch($research),
            $iteration === 1
                ? 'This is the first attempt. Generate the best possible campaign from the start.'
                : $this->formatWeaknesses($iteration, $previousWeaknesses),
            'Write the complete Facebook + Instagram Meta Ads package exactly as specified in your instructions.',
        ]));
    }

    /**
     * @param  list<array{score: string, current: int, target: int, gap: int, explanation: string}>  $weaknesses
     */
    private function formatWeaknesses(int $iteration, array $weaknesses): string
    {
        if ($weaknesses === []) {
            return "Iteration {$iteration}. No specific weaknesses recorded — refine broadly.";
        }

        $lines = array_map(
            static fn (array $w): string => "- {$w['score']}: was {$w['current']}, needs {$w['target']}+. Why it failed: {$w['explanation']}",
            $weaknesses,
        );

        return "Iteration {$iteration} of ".CampaignQualityEvaluator::MAX_ITERATIONS.". Previous attempt failed these scores:\n"
            .implode("\n", $lines)
            ."\nDo NOT repeat the same content — change the angle, hook, or evidence for each failing score while keeping what worked.";
    }

    /**
     * @param  list<array{title: string, url: string, content: string, score: float}>  $research
     */
    private function formatResearch(array $research): string
    {
        if ($research === []) {
            return 'No fresh research available — rely on your general knowledge and say so honestly where relevant.';
        }

        return implode("\n", array_map(
            static fn (array $r): string => "- {$r['title']} ({$r['url']}): {$r['content']}",
            array_slice($research, 0, 10),
        ));
    }

    /**
     * Wraps the agent's short concept in a deterministic template so every
     * image stays on-brand (dark navy + electric purple) regardless of what
     * the model would otherwise invent — see {@see BrandPalette}. Mirrors
     * SocialMedia's `generateAndStoreImage()`.
     */
    private function generateAndStoreImage(string $platform, string $title, string $visual): ?string
    {
        $background = BrandPalette::BACKGROUND;
        $primaryAccent = BrandPalette::PRIMARY_ACCENT;
        $secondaryAccent = BrandPalette::SECONDARY_ACCENT;

        $prompt = <<<PROMPT
            Premium tech advertising graphic, dark mode, minimalist, high-end.
            Background: deep navy blue ({$background}) with a subtle gradient
            and soft cinematic lighting from top. Centered composition: {$visual},
            rendered as a glowing 3D glass-and-neon element in electric purple
            ({$primaryAccent}) with soft accents in ({$secondaryAccent}),
            soft rim light, subtle reflections. Below it, one short title in clean
            bold sans-serif: "{$title}". Generous negative space, thin geometric
            accent lines, faint grid texture. Aesthetic: engineered, editorial,
            Apple-keynote quality. Sharp focus, depth of field, 4k. No extra text,
            no paragraphs, no watermark.
            PROMPT;

        $image = $this->ai->generateImage($prompt, provider: null, size: '1:1', quality: 'high');

        $extension = str_contains($image['mime'], 'png') ? 'png' : 'jpg';
        $path = 'campaigns/ai/'.$platform.'/'.Str::uuid7().'.'.$extension;

        return $this->storage->put($path, base64_decode($image['base64'], true) ?: '', 'public');
    }

    /**
     * Deterministic cache key for one AI operation, scoped by every input
     * field that affects the output. `$causer` is deliberately excluded —
     * two different users requesting the identical payload should share the
     * cache entry.
     *
     * @param  array<string, mixed>  $payload
     */
    private function cacheKey(string $operation, array $payload): string
    {
        return 'campaigns:ai:'.$operation.':'.md5(json_encode($payload, JSON_THROW_ON_ERROR));
    }

    private function broadcast(
        ?object $causer,
        string $campaignUuid,
        string $stage,
        string $message,
        int $progress,
        int $iteration,
    ): void {
        if (! $causer instanceof Authenticatable) {
            return;
        }

        broadcast(new CampaignAiGenerationProgress(
            userId: (int) $causer->getAuthIdentifier(),
            campaignUuid: $campaignUuid,
            stage: $stage,
            message: $message,
            progress: $progress,
            iteration: $iteration,
        ));
    }
}
