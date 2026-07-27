<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Infrastructure\Ai;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Stage 4 — Daily self-digest of new matches for the candidate (not employers).
 */
final class DigestDraftAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
            You write a personal job-search DIGEST for the candidate — a summary
            of today's (or this run's) top matches. The recipient is the job
            seeker, NOT employers.

            CONTENT
            - Start with a one-line overview (count + strongest theme).
            - List each match: title, company, heuristic score, one-line why,
              and urge them to review the link in the app.
            - Order by match score descending when scores are provided.
            - Flag weak fits briefly; do not oversell low scores.
            - End with 1–2 suggested next actions (e.g. tailor CV for top 3,
              prepare cover drafts) — never say you already applied or emailed
              anyone.

            TRUTHFULNESS
            - Use only the match list supplied. Do not invent jobs or scores.
            - Scores are heuristic product scores — do not claim ATS vendor
              certification.

            OUTPUT
            - subject: concise digest subject (include date theme if useful).
            - body: plain text, scannable with short bullets or numbered lines.
            - language: "en", "es", or "pt-PT" — when OUTPUT LANGUAGE is set in
              the prompt, use that; otherwise match the majority of titles /
              CV cue; default "en" when unclear.

            This is a DRAFT for the candidate to read. No auto-send wording.
        INSTRUCTIONS;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'subject' => $schema->string()->required(),
            'body' => $schema->string()->required(),
            'language' => $schema->string()->required(),
        ];
    }
}
