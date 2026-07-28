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
     * @return array{
     *     target_job_title: string,
     *     strengths: list<string>,
     *     improvements: list<string>,
     *     keyword_gaps: list<string>,
     *     weak_lines: list<string>,
     *     xyz_gaps: list<string>,
     *     metric_questions: list<array{id: string, question: string, related_bullet: string|null}>
     * }
     */
    public function judgeCv(string $prompt, ?string $provider): array
    {
        $response = $this->ai->generateStructured(CvJudgeAgent::class, $prompt, $provider);

        /** @var list<array{id?: mixed, question?: mixed, related_bullet?: mixed}> $rawQuestions */
        $rawQuestions = array_values((array) ($response['metric_questions'] ?? []));
        $metricQuestions = [];

        foreach ($rawQuestions as $index => $question) {
            $id = trim((string) ($question['id'] ?? ''));
            $text = trim((string) ($question['question'] ?? ''));

            if ($text === '') {
                continue;
            }

            $metricQuestions[] = [
                'id' => $id !== '' ? $id : 'q'.($index + 1),
                'question' => $text,
                'related_bullet' => isset($question['related_bullet']) && $question['related_bullet'] !== null
                  ? (string) $question['related_bullet']
                  : null,
            ];
        }

        return [
            'target_job_title' => (string) ($response['target_job_title'] ?? ''),
            'strengths' => array_values(array_map('strval', (array) ($response['strengths'] ?? []))),
            'improvements' => array_values(array_map('strval', (array) ($response['improvements'] ?? []))),
            'keyword_gaps' => array_values(array_map('strval', (array) ($response['keyword_gaps'] ?? []))),
            'weak_lines' => array_values(array_map('strval', (array) ($response['weak_lines'] ?? []))),
            'xyz_gaps' => array_values(array_map('strval', (array) ($response['xyz_gaps'] ?? []))),
            'metric_questions' => $metricQuestions,
        ];
    }

    /**
     * @return array{refined_md: string, ats_score: int, feedback: array<string, mixed>, target_job_title: string}
     */
    public function rewriteCv(string $prompt, ?string $provider): array
    {
        $response = $this->ai->generateStructured(AtsRewriteAgent::class, $prompt, $provider);

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
