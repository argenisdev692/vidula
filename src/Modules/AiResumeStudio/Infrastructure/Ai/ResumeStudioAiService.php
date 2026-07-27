<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Infrastructure\Ai;

use Shared\Infrastructure\AI\AIClientInterface;

/**
 * Thin facade over structured-output agents for the studio pipeline.
 */
final readonly class ResumeStudioAiService
{
    public function __construct(private AIClientInterface $ai) {}

    /**
     * @return array{refined_md: string, ats_score: int, feedback: array<string, mixed>, target_job_title: string}
     */
    public function refineCv(string $prompt, ?string $provider): array
    {
        $response = $this->ai->generateStructured(AtsRefineAgent::class, $prompt, $provider);

        return [
            'refined_md' => (string) ($response['refined_md'] ?? ''),
            'ats_score' => (int) ($response['ats_score'] ?? 0),
            'feedback' => (array) ($response['feedback'] ?? []),
            'target_job_title' => (string) ($response['target_job_title'] ?? ''),
        ];
    }

    /**
     * @return array{match_score: int, match_reasoning: string, company_name: string|null}
     */
    public function scoreMatch(string $prompt, ?string $provider): array
    {
        $response = $this->ai->generateStructured(JobMatchScorerAgent::class, $prompt, $provider);

        return [
            'match_score' => (int) ($response['match_score'] ?? 0),
            'match_reasoning' => (string) ($response['match_reasoning'] ?? ''),
            'company_name' => isset($response['company_name']) ? (string) $response['company_name'] : null,
        ];
    }

    /**
     * @return array{subject: string, body: string, language: string}
     */
    public function draftCover(string $prompt, ?string $provider): array
    {
        $response = $this->ai->generateStructured(CoverDraftAgent::class, $prompt, $provider);

        return [
            'subject' => (string) ($response['subject'] ?? ''),
            'body' => (string) ($response['body'] ?? ''),
            'language' => (string) ($response['language'] ?? 'en'),
        ];
    }

    /**
     * @return array{subject: string, body: string, language: string}
     */
    public function draftDigest(string $prompt, ?string $provider): array
    {
        $response = $this->ai->generateStructured(DigestDraftAgent::class, $prompt, $provider);

        return [
            'subject' => (string) ($response['subject'] ?? ''),
            'body' => (string) ($response['body'] ?? ''),
            'language' => (string) ($response['language'] ?? 'en'),
        ];
    }
}
