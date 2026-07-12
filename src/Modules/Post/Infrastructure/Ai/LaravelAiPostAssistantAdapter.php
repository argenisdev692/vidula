<?php

declare(strict_types=1);

namespace Modules\Post\Infrastructure\Ai;

use Illuminate\Support\Str;
use Modules\Post\Application\DTOs\GeneratedPostContentData;
use Modules\Post\Application\DTOs\GeneratePostContentData;
use Modules\Post\Application\DTOs\PostTopicIdeaData;
use Modules\Post\Application\DTOs\SuggestPostTopicsData;
use Modules\Post\Domain\Ports\PostContentGeneratorPort;
use Modules\Post\Domain\Ports\PostTopicIdeatorPort;
use Shared\Domain\Ports\StoragePort;
use Shared\Infrastructure\AI\AIClientInterface;
use Shared\Infrastructure\Company\CompanyProfile;
use Shared\Infrastructure\Research\TavilyClientInterface;

/**
 * Single adapter behind both AI ports the Post module needs — they share the
 * same underlying `laravel/ai` + Tavily infrastructure, so one class composing
 * both agents avoids duplicating the research/prompt-assembly plumbing while
 * each port stays small (ISP) for its own consumer.
 */
final readonly class LaravelAiPostAssistantAdapter implements PostContentGeneratorPort, PostTopicIdeatorPort
{
    public function __construct(
        private AIClientInterface $ai,
        private TavilyClientInterface $research,
        private StoragePort $storage,
    ) {}

    public function suggestTopics(SuggestPostTopicsData $data): array
    {
        $company = CompanyProfile::data();
        $niche = $data->topic ?? $company['description'] ?? $company['name'];

        $research = $this->research->search([
            "{$niche} trends 2026",
            "{$niche} viral content ideas",
            "{$niche} audience pain points",
        ]);

        $prompt = $this->buildTopicsPrompt($data, $company, $research);

        $response = $this->ai->generateStructured(SuggestPostTopicsAgent::class, $prompt, $data->provider);

        return array_map(
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
    }

    public function generate(GeneratePostContentData $data): GeneratedPostContentData
    {
        $company = CompanyProfile::data();

        $research = $this->research->search(array_filter([
            "{$data->topic} 2026",
            $data->keyTrend !== null ? "{$data->keyTrend} statistics recent data" : null,
            "{$data->topic} case study results",
        ]));

        $prompt = $this->buildContentPrompt($data, $company, $research);

        $response = $this->ai->generateStructured(GeneratePostContentAgent::class, $prompt, $data->provider);

        $coverImagePath = $data->generateCoverImage
            ? $this->generateAndStoreCoverImage((string) $response['cover_image_prompt'])
            : null;
        $coverImageUrl = $coverImagePath !== null ? $this->storage->publicUrl($coverImagePath) : null;

        /** @var array<string, mixed> $scores */
        $scores = (array) $response['scores'];
        /** @var array{primary_keyword: string, lsi_keywords: list<string>} $seoAnalysis */
        $seoAnalysis = (array) $response['seo_analysis'];

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

    private function generateAndStoreCoverImage(string $prompt): ?string
    {
        $image = $this->ai->generateImage($prompt, provider: null, size: '16:9', quality: 'high');

        $extension = str_contains($image['mime'], 'png') ? 'png' : 'jpg';
        $path = 'posts/ai/'.Str::uuid7().'.'.$extension;

        return $this->storage->put($path, base64_decode($image['base64'], true) ?: '', 'public');
    }
}
