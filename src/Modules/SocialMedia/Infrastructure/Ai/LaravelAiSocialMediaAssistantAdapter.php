<?php

declare(strict_types=1);

namespace Modules\SocialMedia\Infrastructure\Ai;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Post\Infrastructure\Ai\LaravelAiPostAssistantAdapter;
use Modules\SocialMedia\Application\DTOs\GeneratedSocialMediaContentData;
use Modules\SocialMedia\Application\DTOs\GenerateSocialMediaContentData;
use Modules\SocialMedia\Application\DTOs\PlatformContentData;
use Modules\SocialMedia\Application\DTOs\ScoreResultData;
use Modules\SocialMedia\Application\DTOs\ScoreSetData;
use Modules\SocialMedia\Application\DTOs\SocialMediaTopicIdeaData;
use Modules\SocialMedia\Application\DTOs\SuggestSocialMediaTopicsData;
use Modules\SocialMedia\Domain\Ports\SocialMediaContentGeneratorPort;
use Modules\SocialMedia\Domain\Ports\SocialMediaTopicIdeatorPort;
use Modules\SocialMedia\Domain\Services\ContentQualityEvaluator;
use Modules\SocialMedia\Infrastructure\Broadcasting\SocialMediaAiGenerationProgress;
use Modules\SocialMedia\Infrastructure\Queue\GenerateSocialMediaContentJob;
use Shared\Domain\Ports\SpeechSynthesizerPort;
use Shared\Domain\Ports\StoragePort;
use Shared\Infrastructure\AI\AIClientInterface;
use Shared\Infrastructure\Branding\BrandPalette;
use Shared\Infrastructure\Company\CompanyProfile;
use Shared\Infrastructure\Research\TavilyClientInterface;
use Throwable;

/**
 * Single adapter behind both SocialMedia AI ports — mirrors
 * {@see LaravelAiPostAssistantAdapter}: one
 * class composing the agents so the research/prompt-assembly plumbing is not
 * duplicated, while each port stays small (ISP) for its own consumer. This
 * class performs exactly ONE generation attempt per {@see self::generate()}
 * call — the 5-iteration quality loop is orchestrated by
 * {@see GenerateSocialMediaContentJob},
 * not here.
 *
 * Caching lives HERE, in the module adapter — never in the Shared AI/Tavily
 * clients (those stay pure transport + circuit breaker). `suggestTopics()`
 * always caches (no internal state). `generate()` caches ONLY the first
 * attempt (iteration 1, no previous weaknesses) — from iteration 2 onward
 * the quality loop deliberately targets specific failing scores, so those
 * attempts are never safe to reuse from cache.
 */
final readonly class LaravelAiSocialMediaAssistantAdapter implements SocialMediaContentGeneratorPort, SocialMediaTopicIdeatorPort
{
    private const int CACHE_TTL_MINUTES = 15;

    public function __construct(
        private AIClientInterface $ai,
        private TavilyClientInterface $research,
        private StoragePort $storage,
        private SpeechSynthesizerPort $speech,
    ) {}

    public function suggestTopics(SuggestSocialMediaTopicsData $data, ?object $causer = null): array
    {
        return Cache::remember(
            $this->cacheKey('suggest-topics', $data->toArray()),
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            function () use ($data): array {
                $company = CompanyProfile::data();
                $niche = $data->niche ?? $company['description'] ?? $company['name'];

                $research = $this->research->search([
                    "{$niche} trends 2026",
                    "{$niche} viral content",
                    "{$niche} audience pain points",
                ]);

                $prompt = implode("\n\n", array_filter([
                    "Niche: {$niche}",
                    $data->audience !== null ? "Target audience: {$data->audience}" : 'Target audience: infer from the niche.',
                    $data->businessGoal !== null ? "Business goal: {$data->businessGoal}" : null,
                    "Output language: {$data->language}",
                    'Web research context:'."\n".$this->formatResearch($research),
                    'Generate exactly 10 viral topics as specified in your instructions.',
                ]));

                $response = $this->ai->generateStructured(SuggestSocialMediaTopicsAgent::class, $prompt, $data->provider);

                return array_map(
                    static fn (array $topic): SocialMediaTopicIdeaData => new SocialMediaTopicIdeaData(
                        title: (string) $topic['title'],
                        angle: (string) $topic['angle'],
                        hook: (string) $topic['hook'],
                        platform: (string) $topic['platform'],
                        estimatedVirality: (int) $topic['estimated_virality'],
                        estimatedEngagement: (string) $topic['estimated_engagement'],
                        estimatedRoi: (int) $topic['estimated_roi'],
                        difficulty: (string) $topic['difficulty'],
                        whyItWorks: (string) $topic['why_it_works'],
                        keyTrend: (string) $topic['key_trend'],
                        suggestedFormat: (string) $topic['suggested_format'],
                        contentType: (string) $topic['content_type'],
                        funnelStage: (string) $topic['funnel_stage'],
                    ),
                    (array) $response['viral_topics'],
                );
            },
        );
    }

    public function generate(
        string $contentUuid,
        GenerateSocialMediaContentData $data,
        int $iteration = 1,
        array $previousWeaknesses = [],
        ?object $causer = null,
    ): GeneratedSocialMediaContentData {
        $attempt = fn (): GeneratedSocialMediaContentData => $this->generateAttempt($contentUuid, $data, $iteration, $previousWeaknesses, $causer);

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
        string $contentUuid,
        GenerateSocialMediaContentData $data,
        int $iteration,
        array $previousWeaknesses,
        ?object $causer,
    ): GeneratedSocialMediaContentData {
        $this->broadcast($causer, $contentUuid, 'researching', "Iteration {$iteration}: researching fresh context…", 15, $iteration);

        $company = CompanyProfile::data();
        $research = $this->research->search($this->researchQueries($data, $iteration));
        $prompt = $this->buildContentPrompt($data, $company, $research, $iteration, $previousWeaknesses);

        $this->broadcast($causer, $contentUuid, 'writing', "Iteration {$iteration}: writing the 5-platform package…", 45, $iteration);

        $response = $this->ai->generateStructured(GenerateSocialMediaContentAgent::class, $prompt, $data->provider);

        $this->broadcast($causer, $contentUuid, 'scoring', "Iteration {$iteration}: scoring the attempt…", 70, $iteration);

        $scores = $this->mapScores((array) $response['scores']);

        $platforms = [];

        foreach ((array) $response['platforms'] as $platform => $variation) {
            /** @var array<string, mixed> $variation */
            $platforms[$platform] = $this->buildPlatformContent(
                (string) $platform,
                $variation,
                $data,
                $causer,
                $contentUuid,
                $iteration,
            );
        }

        $this->broadcast($causer, $contentUuid, 'cover_image', "Iteration {$iteration}: generating the cover image…", 90, $iteration);

        /** @var array{title: string, visual: string} $coverConcept */
        $coverConcept = (array) $response['cover_image_concept'];
        $coverImagePath = $data->generateImages
            ? $this->generateAndStoreImage('cover', (string) $coverConcept['title'], (string) $coverConcept['visual'])
            : null;
        $coverImageUrl = $coverImagePath !== null ? $this->storage->publicUrl($coverImagePath) : null;

        $this->broadcast($causer, $contentUuid, 'done', "Iteration {$iteration}: attempt ready.", 100, $iteration);

        /** @var array{content: mixed} $response */
        $content = (array) $response['content'];

        return new GeneratedSocialMediaContentData(
            headline: (string) $content['headline'],
            body: (string) $content['body'],
            callToAction: (string) $content['call_to_action'],
            hashtags: (array) $content['hashtags'],
            platforms: $platforms,
            coverImagePath: $coverImagePath,
            coverImageUrl: $coverImageUrl,
            coverImagePrompt: "{$coverConcept['title']} — {$coverConcept['visual']}",
            scores: $scores,
            eeatAnalysis: (array) $response['eeat_analysis'],
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
    private function buildPlatformContent(
        string $platform,
        array $variation,
        GenerateSocialMediaContentData $data,
        ?object $causer,
        string $contentUuid,
        int $iteration,
    ): PlatformContentData {
        /** @var array{title: string, visual: string} $imageConcept */
        $imageConcept = (array) $variation['image_concept'];

        $imagePath = $data->generateImages
            ? $this->generateAndStoreImage($platform, (string) $imageConcept['title'], (string) $imageConcept['visual'])
            : null;
        $imageUrl = $imagePath !== null ? $this->storage->publicUrl($imagePath) : null;

        $voiceoverPath = null;
        $voiceoverUrl = null;

        if ($platform === 'tiktok' && $data->generateVoiceover) {
            $this->broadcast($causer, $contentUuid, 'voiceover', "Iteration {$iteration}: synthesizing the TikTok voiceover…", 95, $iteration);
            $voiceoverPath = $this->generateAndStoreVoiceover((string) $variation['video_script']);
            $voiceoverUrl = $voiceoverPath !== null ? $this->storage->publicUrl($voiceoverPath) : null;
        }

        return new PlatformContentData(
            platform: $platform,
            adaptedContent: (string) $variation['adapted_content'],
            characterCount: (int) $variation['character_count'],
            hashtags: (array) $variation['hashtags'],
            imagePrompt: "{$imageConcept['title']} — {$imageConcept['visual']}",
            isThread: (bool) ($variation['is_thread'] ?? false),
            threadTweets: (array) ($variation['thread_tweets'] ?? []),
            videoScript: isset($variation['video_script']) ? (string) $variation['video_script'] : null,
            imagePath: $imagePath,
            imageUrl: $imageUrl,
            voiceoverAudioPath: $voiceoverPath,
            voiceoverAudioUrl: $voiceoverUrl,
        );
    }

    /**
     * @param  array<string, mixed>  $rawScores
     */
    private function mapScores(array $rawScores): ScoreSetData
    {
        $toResult = function (string $key, array $raw): ScoreResultData {
            $value = (int) $raw['value'];
            $threshold = ContentQualityEvaluator::THRESHOLDS[$key];

            return new ScoreResultData(
                value: $value,
                threshold: $threshold,
                passes: $value >= $threshold,
                factors: array_map(static fn (mixed $v): int => (int) $v, (array) $raw['factors']),
                explanation: (string) $raw['explanation'],
            );
        };

        $humanWritingIndex = $toResult('human_writing_index', (array) $rawScores['human_writing_index']);
        $viralityScore = $toResult('virality_score', (array) $rawScores['virality_score']);
        $engagementScore = $toResult('engagement_score', (array) $rawScores['engagement_score']);
        $roiScore = $toResult('roi_score', (array) $rawScores['roi_score']);
        $trendAlignment = $toResult('trend_alignment', (array) $rawScores['trend_alignment']);

        $evaluation = (new ContentQualityEvaluator)->evaluate([
            'human_writing_index' => $humanWritingIndex->value,
            'virality_score' => $viralityScore->value,
            'engagement_score' => $engagementScore->value,
            'roi_score' => $roiScore->value,
            'trend_alignment' => $trendAlignment->value,
        ]);

        return new ScoreSetData(
            humanWritingIndex: $humanWritingIndex,
            viralityScore: $viralityScore,
            engagementScore: $engagementScore,
            roiScore: $roiScore,
            trendAlignment: $trendAlignment,
            allScoresPass: $evaluation->allPass,
            overallAverage: $evaluation->overallAverage,
        );
    }

    /**
     * Varies the research queries per iteration (Prompt2's convention) so a
     * retry gets genuinely fresh context instead of repeating the same search.
     *
     * @return list<string>
     */
    private function researchQueries(GenerateSocialMediaContentData $data, int $iteration): array
    {
        $niche = $data->niche ?? $data->topic;
        $base = array_filter([
            "{$data->topic} {$niche} 2026",
            $data->keyTrend !== null ? "{$data->keyTrend} statistics recent data" : null,
            "{$niche} audience insights trends",
        ]);

        return match (true) {
            $iteration >= 4 => [...$base, "{$niche} authority sources citations", "{$data->topic} best practices"],
            $iteration >= 2 => [...$base, "{$niche} viral examples social media", "{$data->topic} engagement benchmarks"],
            default => $base,
        };
    }

    /**
     * @param  array{name: string, description: ?string}  $company
     * @param  list<array{title: string, url: string, content: string, score: float}>  $research
     * @param  list<array{score: string, current: int, target: int, gap: int, explanation: string}>  $previousWeaknesses
     */
    private function buildContentPrompt(
        GenerateSocialMediaContentData $data,
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
            "Output language: {$data->language}",
            'Web research context:'."\n".$this->formatResearch($research),
            $iteration === 1
                ? 'This is the first attempt. Generate the best possible content from the start.'
                : $this->formatWeaknesses($iteration, $previousWeaknesses),
            'Write the complete 5-platform package exactly as specified in your instructions.',
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

        return "Iteration {$iteration} of ".ContentQualityEvaluator::MAX_ITERATIONS.". Previous attempt failed these scores:\n"
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
     * Post's `generateAndStoreCoverImage()`.
     */
    private function generateAndStoreImage(string $platform, string $title, string $visual): ?string
    {
        $background = BrandPalette::BACKGROUND;
        $primaryAccent = BrandPalette::PRIMARY_ACCENT;
        $secondaryAccent = BrandPalette::SECONDARY_ACCENT;

        $prompt = <<<PROMPT
            Premium tech social media graphic, dark mode, minimalist, high-end.
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
        $path = 'social-media/ai/'.$platform.'/'.Str::uuid7().'.'.$extension;

        return $this->storage->put($path, base64_decode($image['base64'], true) ?: '', 'public');
    }

    /**
     * Best-effort TikTok voiceover. Null on any failure (ElevenLabs
     * unreachable/misconfigured) — the script remains fully usable without
     * audio, same contract as Post's Reel package.
     */
    private function generateAndStoreVoiceover(string $videoScript): ?string
    {
        $audio = $this->speech->synthesize($videoScript);

        if ($audio === null) {
            return null;
        }

        $path = 'social-media/ai/tiktok/audio/'.Str::uuid7().'.mp3';
        $stored = $this->storage->put($path, base64_decode($audio['base64'], true) ?: '', 'public');

        return $stored;
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
        return 'social_media:ai:'.$operation.':'.md5(json_encode($payload, JSON_THROW_ON_ERROR));
    }

    private function broadcast(
        ?object $causer,
        string $contentUuid,
        string $stage,
        string $message,
        int $progress,
        int $iteration,
    ): void {
        if (! $causer instanceof Authenticatable) {
            return;
        }

        try {
            broadcast(new SocialMediaAiGenerationProgress(
                userId: (int) $causer->getAuthIdentifier(),
                contentUuid: $contentUuid,
                stage: $stage,
                message: $message,
                progress: $progress,
                iteration: $iteration,
            ));
        } catch (Throwable $exception) {
            Log::warning('social_media.ai.broadcast_failed', ['message' => $exception->getMessage()]);
        }
    }
}
