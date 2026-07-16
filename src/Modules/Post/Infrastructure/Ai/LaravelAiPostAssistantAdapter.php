<?php

declare(strict_types=1);

namespace Modules\Post\Infrastructure\Ai;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Post\Application\DTOs\GenerateContentVariantData;
use Modules\Post\Application\DTOs\GeneratedPostContentData;
use Modules\Post\Application\DTOs\GeneratePostContentData;
use Modules\Post\Application\DTOs\PostTopicIdeaData;
use Modules\Post\Application\DTOs\ReelPackageData;
use Modules\Post\Application\DTOs\ReelSceneData;
use Modules\Post\Application\DTOs\SocialCopyData;
use Modules\Post\Application\DTOs\SuggestPostTopicsData;
use Modules\Post\Domain\Ports\PostContentGeneratorPort;
use Modules\Post\Domain\Ports\PostTopicIdeatorPort;
use Modules\Post\Domain\Ports\ReelPackageGeneratorPort;
use Modules\Post\Domain\Ports\SocialCopyGeneratorPort;
use Modules\Post\Infrastructure\Broadcasting\PostAiGenerationProgress;
use Shared\Domain\Ports\SpeechSynthesizerPort;
use Shared\Domain\Ports\StoragePort;
use Shared\Infrastructure\AI\AIClientInterface;
use Shared\Infrastructure\Branding\BrandPalette;
use Shared\Infrastructure\Company\CompanyProfile;
use Shared\Infrastructure\Research\TavilyClientInterface;
use Throwable;

/**
 * Single adapter behind all four AI ports the Post module needs — they share
 * the same underlying `laravel/ai` + Tavily infrastructure, so one class
 * composing the agents avoids duplicating the research/prompt-assembly
 * plumbing while each port stays small (ISP) for its own consumer.
 *
 * Caching lives HERE, in the module adapter — never in the Shared AI/Tavily
 * clients (`LaravelAIAdapter`/`TavilyResearchAdapter` stay pure transport +
 * circuit breaker, no business/result caching). Each of these four calls is
 * a real, billed provider request with no internal iteration state, so the
 * full result is cacheable keyed on its input payload — dedupes accidental
 * double-submits/rapid retries with identical parameters within the TTL
 * without ever risking stale content past it.
 */
final readonly class LaravelAiPostAssistantAdapter implements PostContentGeneratorPort, PostTopicIdeatorPort, ReelPackageGeneratorPort, SocialCopyGeneratorPort
{
    private const int CACHE_TTL_MINUTES = 15;

    public function __construct(
        private AIClientInterface $ai,
        private TavilyClientInterface $research,
        private StoragePort $storage,
        private SpeechSynthesizerPort $speech,
    ) {}

    public function suggestTopics(SuggestPostTopicsData $data, ?object $causer = null): array
    {
        return Cache::remember(
            $this->cacheKey('suggest-topics', $data->toArray()),
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            function () use ($data, $causer): array {
                $this->broadcast($causer, 'topics', 'researching', 'Researching current trends…', 20);

                $company = CompanyProfile::data();
                $niche = $data->topic ?? $company['description'] ?? $company['name'];

                $research = $this->research->search([
                    "{$niche} trends 2026",
                    "{$niche} viral content ideas",
                    "{$niche} audience pain points",
                ]);

                $prompt = $this->buildTopicsPrompt($data, $company, $research);

                $this->broadcast($causer, 'topics', 'generating', 'Drafting topic ideas…', 60);

                $response = $this->ai->generateStructured(SuggestPostTopicsAgent::class, $prompt, $data->provider);

                $ideas = array_map(
                    static fn (array $idea): PostTopicIdeaData => new PostTopicIdeaData(
                        title: (string) $idea['title'],
                        angle: (string) $idea['angle'],
                        hook: (string) $idea['hook'],
                        estimatedVirality: (int) $idea['estimated_virality'],
                        estimatedRoi: (int) $idea['estimated_roi'],
                        eeatPotential: (int) $idea['eeat_potential'],
                        whyItWorks: (string) $idea['why_it_works'],
                        keyTrend: (string) $idea['key_trend'],
                    ),
                    (array) $response['content_ideas'],
                );

                $this->broadcast($causer, 'topics', 'done', 'Topic ideas ready.', 100);

                return $ideas;
            },
        );
    }

    public function generate(GeneratePostContentData $data, ?object $causer = null): GeneratedPostContentData
    {
        return Cache::remember(
            $this->cacheKey('generate', $data->toArray()),
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            function () use ($data, $causer): GeneratedPostContentData {
                $this->broadcast($causer, 'content', 'researching', 'Researching current trends…', 15);

                $company = CompanyProfile::data();
                $research = $this->research->search($this->researchQueries($data->topic, $data->keyTrend));
                $prompt = $this->buildContentPrompt($data, $company, $research);

                $this->broadcast($causer, 'content', 'writing', 'Writing the blog draft…', 50);

                $response = $this->ai->generateStructured(GeneratePostContentAgent::class, $prompt, $data->provider);

                /** @var array{title: string, visual: string} $concept */
                $concept = (array) $response['cover_image_concept'];
                $coverImagePath = null;

                if ($data->generateCoverImage) {
                    $this->broadcast($causer, 'content', 'image', 'Generating the on-brand cover image…', 80);
                    $coverImagePath = $this->generateAndStoreCoverImage((string) $concept['title'], (string) $concept['visual']);
                }

                $coverImageUrl = $coverImagePath !== null ? $this->storage->publicUrl($coverImagePath) : null;

                /** @var array<string, mixed> $scores */
                $scores = (array) $response['scores'];
                /** @var array{primary_keyword: string, lsi_keywords: list<string>} $seoAnalysis */
                $seoAnalysis = (array) $response['seo_analysis'];

                $this->broadcast($causer, 'content', 'done', 'Draft ready.', 100);

                return new GeneratedPostContentData(
                    title: (string) $response['title'],
                    content: (string) $response['content'],
                    excerpt: (string) $response['excerpt'],
                    metaTitle: (string) $response['meta_title'],
                    metaDescription: (string) $response['meta_description'],
                    metaKeywords: (string) $response['meta_keywords'],
                    coverImagePath: $coverImagePath,
                    coverImageUrl: $coverImageUrl,
                    provider: $data->provider,
                    seoScore: (int) $scores['seo_score'],
                    eeatScore: (int) $scores['eeat_score'],
                    humanWritingIndex: (int) $scores['human_writing_index'],
                    aiDetectionRisk: (int) $scores['ai_detection_risk'],
                    scores: $scores,
                    optimizationSuggestions: (array) $response['optimization_suggestions'],
                    seoAnalysis: $seoAnalysis,
                );
            },
        );
    }

    public function generateSocialCopy(GenerateContentVariantData $data, ?object $causer = null): SocialCopyData
    {
        return Cache::remember(
            $this->cacheKey('generate-social-copy', $data->toArray()),
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            function () use ($data, $causer): SocialCopyData {
                $this->broadcast($causer, 'social', 'researching', 'Researching current trends…', 25);

                $company = CompanyProfile::data();
                $research = $this->research->search($this->researchQueries($data->topic, $data->keyTrend));
                $prompt = $this->buildVariantPrompt(
                    $data,
                    $company,
                    $research,
                    'Write the LinkedIn post and the Instagram/Facebook caption exactly as specified in your instructions.',
                );

                $this->broadcast($causer, 'social', 'writing', 'Writing the social copy…', 70);

                $response = $this->ai->generateStructured(GenerateSocialCopyAgent::class, $prompt, $data->provider);

                $this->broadcast($causer, 'social', 'done', 'Social copy ready.', 100);

                return new SocialCopyData(
                    linkedinPost: (string) $response['linkedin_post'],
                    socialCaption: (string) $response['social_caption'],
                    hashtags: (array) $response['hashtags'],
                );
            },
        );
    }

    public function generateReelPackage(GenerateContentVariantData $data, ?object $causer = null): ReelPackageData
    {
        return Cache::remember(
            $this->cacheKey('generate-reel-package', $data->toArray()),
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            function () use ($data, $causer): ReelPackageData {
                $this->broadcast($causer, 'reel', 'researching', 'Researching current trends…', 15);

                $company = CompanyProfile::data();
                $research = $this->research->search($this->researchQueries($data->topic, $data->keyTrend));
                $prompt = $this->buildVariantPrompt(
                    $data,
                    $company,
                    $research,
                    'Write the complete Reel/TikTok package exactly as specified in your instructions.',
                );

                $this->broadcast($causer, 'reel', 'writing', 'Writing the Reel/TikTok script…', 45);

                $response = $this->ai->generateStructured(GenerateReelPackageAgent::class, $prompt, $data->provider);

                $scenes = array_map(
                    static fn (array $scene): ReelSceneData => new ReelSceneData(
                        timeRange: (string) $scene['time_range'],
                        action: (string) $scene['action'],
                        onScreenText: (string) $scene['on_screen_text'],
                        voiceoverLine: (string) $scene['voiceover_line'],
                        visualPrompt: (string) $scene['visual_prompt'],
                    ),
                    (array) $response['scenes'],
                );

                $cleanScript = (string) $response['clean_script'];

                $this->broadcast($causer, 'reel', 'voiceover', 'Synthesizing the AI voiceover…', 80);

                $voiceoverAudioUrl = $this->generateAndStoreVoiceover($cleanScript);

                $this->broadcast($causer, 'reel', 'done', 'Reel package ready.', 100);

                return new ReelPackageData(
                    scenes: $scenes,
                    cleanScript: $cleanScript,
                    soundSuggestion: (string) $response['sound_suggestion'],
                    tiktokCaption: (string) $response['tiktok_caption'],
                    tiktokHashtags: (array) $response['tiktok_hashtags'],
                    voiceoverAudioUrl: $voiceoverAudioUrl,
                );
            },
        );
    }

    /**
     * Pushes a real-time progress tick to the causer's own private channel
     * (see {@see PostAiGenerationProgress}). Silently a no-op when there is no
     * authenticated causer (e.g. a future system-triggered call) — the
     * generation itself never depends on the socket push succeeding.
     */
    private function broadcast(?object $causer, string $flow, string $stage, string $message, int $progress): void
    {
        if (! $causer instanceof Authenticatable) {
            return;
        }

        try {
            broadcast(new PostAiGenerationProgress(
                userId: (int) $causer->getAuthIdentifier(),
                flow: $flow,
                stage: $stage,
                message: $message,
                progress: $progress,
            ));
        } catch (Throwable $exception) {
            Log::warning('post.ai.broadcast_failed', ['message' => $exception->getMessage()]);
        }
    }

    /**
     * Deterministic cache key for one AI operation, scoped by every input
     * field that affects the output (via the DTO's own `toArray()` — stays
     * correct automatically if a field is ever added). `$causer` is
     * deliberately excluded: two different users requesting the identical
     * payload should share the cache entry.
     *
     * @param  array<string, mixed>  $payload
     */
    private function cacheKey(string $operation, array $payload): string
    {
        return 'post:ai:'.$operation.':'.md5(json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /**
     * @return list<string>
     */
    private function researchQueries(string $topic, ?string $keyTrend): array
    {
        return array_filter([
            "{$topic} 2026",
            $keyTrend !== null ? "{$keyTrend} statistics recent data" : null,
            "{$topic} case study results",
        ]);
    }

    /**
     * @param  array{name: string, description: ?string}  $company
     * @param  list<array{title: string, url: string, content: string, score: float}>  $research
     */
    private function buildTopicsPrompt(SuggestPostTopicsData $data, array $company, array $research): string
    {
        return implode("\n\n", array_filter([
            "Company: {$company['name']}",
            $company['description'] !== null ? "Company description: {$company['description']}" : null,
            $data->topic !== null ? "Requested topic steer: {$data->topic}" : 'No specific topic given — propose a broad spread across the company\'s niche.',
            'Web research context:'."\n".$this->formatResearch($research),
            'Generate exactly 10 blog topic ideas as specified in your instructions.',
        ]));
    }

    /**
     * @param  array{name: string, description: ?string}  $company
     * @param  list<array{title: string, url: string, content: string, score: float}>  $research
     */
    private function buildContentPrompt(GeneratePostContentData $data, array $company, array $research): string
    {
        return implode("\n\n", array_filter([
            "Company: {$company['name']}",
            $company['description'] !== null ? "Company description: {$company['description']}" : null,
            "Topic: {$data->topic}",
            $data->angle !== null ? "Angle: {$data->angle}" : null,
            $data->keyTrend !== null ? "Key trend to reference: {$data->keyTrend}" : null,
            'Web research context:'."\n".$this->formatResearch($research),
            'Write the complete blog post exactly as specified in your instructions.',
        ]));
    }

    /**
     * @param  array{name: string, description: ?string}  $company
     * @param  list<array{title: string, url: string, content: string, score: float}>  $research
     */
    private function buildVariantPrompt(
        GenerateContentVariantData $data,
        array $company,
        array $research,
        string $instruction,
    ): string {
        return implode("\n\n", array_filter([
            "Company: {$company['name']}",
            $company['description'] !== null ? "Company description: {$company['description']}" : null,
            "Topic: {$data->topic}",
            $data->angle !== null ? "Angle: {$data->angle}" : null,
            $data->keyTrend !== null ? "Key trend to reference: {$data->keyTrend}" : null,
            'Web research context:'."\n".$this->formatResearch($research),
            $instruction,
        ]));
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
     * cover image stays on-brand (dark navy + electric purple) regardless of
     * what the model would otherwise invent — see {@see BrandPalette}.
     */
    private function generateAndStoreCoverImage(string $title, string $visual): ?string
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

        $image = $this->ai->generateImage($prompt, provider: null, size: '16:9', quality: 'high');

        $extension = str_contains($image['mime'], 'png') ? 'png' : 'jpg';
        $path = 'posts/ai/'.Str::uuid7().'.'.$extension;

        return $this->storage->put($path, base64_decode($image['base64'], true) ?: '', 'public');
    }

    /**
     * Best-effort voiceover for the Reel's clean script. Null on any failure
     * (ElevenLabs unreachable/misconfigured) — the script/timeline remain
     * fully usable without audio.
     */
    private function generateAndStoreVoiceover(string $cleanScript): ?string
    {
        $audio = $this->speech->synthesize($cleanScript);

        if ($audio === null) {
            return null;
        }

        $path = 'posts/ai/audio/'.Str::uuid7().'.mp3';
        $stored = $this->storage->put($path, base64_decode($audio['base64'], true) ?: '', 'public');

        return $this->storage->publicUrl($stored);
    }
}
