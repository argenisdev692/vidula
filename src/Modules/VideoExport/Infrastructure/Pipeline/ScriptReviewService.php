<?php

declare(strict_types=1);

namespace Modules\VideoExport\Infrastructure\Pipeline;

use Illuminate\Support\Facades\Http;
use Modules\VideoExport\Infrastructure\Ai\ReviewScriptAgainstTranscriptAgent;
use Shared\Infrastructure\AI\AIClientInterface;
use Throwable;

final readonly class ScriptReviewService
{
    public function __construct(private AIClientInterface $ai) {}

    /**
     * @param  'openai'|'anthropic'|'gemini'|null  $provider
     * @return array{review: string, leftover_pause_fragments: int, compliance_score: int, error?: string}
     */
    public function review(?string $scriptUrl, string $transcriptPlain, ?string $provider = null): array
    {
        if ($scriptUrl === null || $scriptUrl === '') {
            return [
                'review' => '',
                'leftover_pause_fragments' => 0,
                'compliance_score' => 0,
            ];
        }

        $resolvedProvider = $provider
            ?? (string) config('ai.default', 'gemini');

        try {
            $scriptBody = Http::timeout(60)->get($scriptUrl)->body();
            $prompt = "SCRIPT:\n".mb_substr($scriptBody, 0, 40_000)
                ."\n\nTRANSCRIPT:\n".mb_substr($transcriptPlain, 0, 40_000);

            $response = $this->ai->generateStructured(
                ReviewScriptAgainstTranscriptAgent::class,
                $prompt,
                $resolvedProvider,
            );

            return [
                'review' => (string) ($response['review_markdown'] ?? ''),
                'leftover_pause_fragments' => (int) ($response['leftover_pause_fragments'] ?? 0),
                'compliance_score' => (int) ($response['compliance_score'] ?? 0),
            ];
        } catch (Throwable $e) {
            return [
                'review' => '',
                'leftover_pause_fragments' => 0,
                'compliance_score' => 0,
                'error' => 'Script review failed.',
            ];
        }
    }
}
