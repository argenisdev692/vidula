<?php

declare(strict_types=1);

namespace Modules\Products\Infrastructure\Ai;

use Illuminate\Support\Facades\Cache;
use Modules\Products\Application\DTOs\ConsistencyReportData;
use Modules\Products\Application\DTOs\GeneratedTopicContentData;
use Modules\Products\Application\DTOs\TopicGenerationRequestData;
use Modules\Products\Domain\Enums\ScriptStatus;
use Modules\Products\Domain\Ports\ProductContentGeneratorPort;
use Modules\Products\Domain\Services\LibraryNameDetector;
use Modules\Products\Infrastructure\Queue\GenerateProductContentJob;
use Shared\Domain\Ports\DocsVerificationPort;
use Shared\Infrastructure\AI\AIClientInterface;
use Shared\Infrastructure\Research\TavilyClientInterface;

/**
 * Single adapter behind {@see ProductContentGeneratorPort}, composing the
 * three Products agents with the two grounding sources — the same
 * "one adapter, several agents" shape Campaigns and SocialMedia use, so the
 * research/prompt-assembly plumbing exists once.
 *
 * One call = one topic. The per-topic loop, the progress broadcast and the
 * continue-on-failure policy belong to
 * {@see GenerateProductContentJob}.
 *
 * Caching is scoped to the DOCS lookups only: Context7 answers are stable for
 * hours and rate-limited, while the AI call itself must not be replayed —
 * an operator who re-runs a generation is explicitly asking for new content.
 * Caching never lives in the Shared clients (pure transport + breaker).
 *
 * Degradation: when neither Tavily nor Context7 returns anything, the topic is
 * still written from the model's own knowledge but comes back as
 * {@see ScriptStatus::NeedsReview} so nothing ungrounded reaches a customer
 * unreviewed (spec FR-15).
 */
final readonly class LaravelAiProductGeneratorAdapter implements ProductContentGeneratorPort
{
    private const int DOCS_CACHE_TTL_MINUTES = 720;

    private const int MAX_RESEARCH_RESULTS_IN_PROMPT = 6;

    private const int MAX_DOC_SNIPPETS_IN_PROMPT = 6;

    public function __construct(
        private AIClientInterface $ai,
        private TavilyClientInterface $research,
        private DocsVerificationPort $docs,
        private LibraryNameDetector $libraries,
    ) {}

    public function generateTopic(TopicGenerationRequestData $request): GeneratedTopicContentData
    {
        $research = $this->research->search($this->researchQueries($request));
        $documentation = $this->documentationFor($request);
        $sources = $this->mergeSources($research, $documentation);

        $agent = $request->productType->isVideo() ? TopicScriptAgent::class : ClassroomLessonAgent::class;
        $prompt = $this->buildTopicPrompt($request, $research, $documentation);

        $response = $this->ai->generateStructured($agent, $prompt, $request->provider);

        $isVideo = $request->productType->isVideo();
        $notes = (string) $response['notes'];

        if (! $isVideo) {
            $notes = $this->appendExercises($notes, (array) $response['exercises']);
        }

        return new GeneratedTopicContentData(
            intro: $isVideo ? (string) $response['intro'] : null,
            body: (string) $response['body'],
            outro: $isVideo ? (string) $response['outro'] : null,
            notes: $notes,
            estimatedMinutes: (int) $response['estimated_minutes'],
            keyPoints: array_map(strval(...), (array) $response['key_points']),
            sources: $sources,
            status: $sources === [] ? ScriptStatus::NeedsReview : ScriptStatus::Generated,
            model: $this->modelLabel($request->provider),
        );
    }

    public function verifyConsistency(string $productTitle, array $seedTitles, array $generatedTitles, ?string $provider = null): ConsistencyReportData
    {
        $prompt = implode("\n\n", [
            "Course: {$productTitle}",
            "SEED TOPICS (what was promised):\n".$this->numbered($seedTitles),
            "GENERATED TOPICS (what exists now):\n".$this->numbered($generatedTitles),
            'Audit the generated set against the seed exactly as specified in your instructions.',
        ]);

        $response = $this->ai->generateStructured(ConsistencyAgent::class, $prompt, $provider);

        return new ConsistencyReportData(
            consistent: (bool) $response['consistent'],
            coverageScore: (int) $response['coverage_score'],
            missingTitles: array_map(strval(...), (array) $response['missing_titles']),
            driftedTopics: array_map(
                static fn (array $topic): array => [
                    'seed_title' => (string) $topic['seed_title'],
                    'generated_title' => (string) $topic['generated_title'],
                    'reason' => (string) $topic['reason'],
                ],
                (array) $response['drifted_topics'],
            ),
            summary: (string) $response['summary'],
        );
    }

    /**
     * @return list<string>
     */
    private function researchQueries(TopicGenerationRequestData $request): array
    {
        $year = now()->year;

        return [
            "{$request->topicTitle} {$request->productTitle}",
            "{$request->topicTitle} tutorial best practices {$year}",
            "{$request->topicTitle} common mistakes examples",
        ];
    }

    /**
     * Official documentation for whichever libraries the topic actually names.
     * Each library is cached independently so sibling topics about the same
     * stack share one Context7 round-trip.
     *
     * @return list<array{library: string, title: string, url: string, snippet: string}>
     */
    private function documentationFor(TopicGenerationRequestData $request): array
    {
        $libraries = $this->libraries->detect($request->topicTitle, $request->sessionTitle, $request->productTitle);
        $snippets = [];

        foreach ($libraries as $library) {
            $topicContext = "{$request->topicTitle} — {$request->sessionTitle}";

            $found = Cache::tags(['products', 'docs'])->remember(
                'products:docs:'.md5($library.'|'.$topicContext),
                now()->addMinutes(self::DOCS_CACHE_TTL_MINUTES),
                fn (): array => $this->docs->lookup($library, $topicContext),
            );

            foreach ($found as $snippet) {
                $snippets[] = $snippet;
            }
        }

        return $snippets;
    }

    /**
     * @param  list<array{title: string, url: string, content: string, score: float}>  $research
     * @param  list<array{library: string, title: string, url: string, snippet: string}>  $documentation
     * @return list<array{type: string, title: string, url: string, snippet: string}>
     */
    private function mergeSources(array $research, array $documentation): array
    {
        $sources = array_map(
            static fn (array $doc): array => [
                'type' => 'documentation',
                'title' => $doc['library'].' — '.$doc['title'],
                'url' => $doc['url'],
                'snippet' => $doc['snippet'],
            ],
            $documentation,
        );

        foreach ($research as $result) {
            $sources[] = [
                'type' => 'web',
                'title' => $result['title'],
                'url' => $result['url'],
                'snippet' => $result['content'],
            ];
        }

        return $sources;
    }

    /**
     * @param  list<array{title: string, url: string, content: string, score: float}>  $research
     * @param  list<array{library: string, title: string, url: string, snippet: string}>  $documentation
     */
    private function buildTopicPrompt(TopicGenerationRequestData $request, array $research, array $documentation): string
    {
        $siblings = $request->siblingTopicTitles !== []
            ? "Other topics in this same session (do NOT teach them here, only reference them):\n".$this->numbered($request->siblingTopicTitles)
            : null;

        return implode("\n\n", array_filter([
            "Course: {$request->productTitle}",
            'Course format: '.$request->productType->label(),
            "Session {$request->sessionNumber}: {$request->sessionTitle}",
            "Topic to write: {$request->topicTitle}",
            $request->topicDescription !== null ? "Topic detail from the operator's index: {$request->topicDescription}" : null,
            $siblings,
            "Output language: {$request->language}",
            'OFFICIAL DOCUMENTATION:'."\n".$this->formatDocumentation($documentation),
            'WEB RESEARCH:'."\n".$this->formatResearch($research),
            'Write this single topic exactly as specified in your instructions.',
        ]));
    }

    /**
     * @param  list<array{library: string, title: string, url: string, snippet: string}>  $documentation
     */
    private function formatDocumentation(array $documentation): string
    {
        if ($documentation === []) {
            return 'None available for this topic — do not make version-specific claims.';
        }

        return implode("\n", array_map(
            static fn (array $doc): string => "- [{$doc['library']}] {$doc['title']} ({$doc['url']}): {$doc['snippet']}",
            array_slice($documentation, 0, self::MAX_DOC_SNIPPETS_IN_PROMPT),
        ));
    }

    /**
     * @param  list<array{title: string, url: string, content: string, score: float}>  $research
     */
    private function formatResearch(array $research): string
    {
        if ($research === []) {
            return 'No fresh research available — rely on stable fundamentals and say so honestly where relevant.';
        }

        return implode("\n", array_map(
            static fn (array $result): string => "- {$result['title']} ({$result['url']}): {$result['content']}",
            array_slice($research, 0, self::MAX_RESEARCH_RESULTS_IN_PROMPT),
        ));
    }

    /**
     * Classroom exercises ride along in the instructor notes: the unified
     * script row has no exercises column, and they are worthless separated
     * from the delivery guidance they belong to.
     *
     * @param  array<int, mixed>  $exercises
     */
    private function appendExercises(string $notes, array $exercises): string
    {
        if ($exercises === []) {
            return $notes;
        }

        $lines = array_map(static fn (mixed $exercise): string => '- '.(string) $exercise, $exercises);

        return $notes."\n\n## Exercises\n".implode("\n", $lines);
    }

    /**
     * @param  list<string>  $items
     */
    private function numbered(array $items): string
    {
        if ($items === []) {
            return '(none)';
        }

        $lines = [];

        foreach ($items as $index => $item) {
            $lines[] = ($index + 1).'. '.$item;
        }

        return implode("\n", $lines);
    }

    private function modelLabel(?string $provider): string
    {
        return $provider ?? (string) config('ai.default');
    }
}
