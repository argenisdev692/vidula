<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Infrastructure\Ai;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Stage 2 — Job match score vs refined CV (heuristic, evidence-based).
 */
final class JobMatchScorerAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
            You are a senior technical recruiter evaluating fit between ONE
            refined CV and ONE job posting.

            SCORING RUBRIC (heuristic 0–100 — not a vendor ATS score)
            - 85–100: Strong match on must-have skills, seniority, and domain.
            - 70–84: Solid match; 1–2 gaps that are trainable or secondary.
            - 50–69: Partial match; several must-haves missing or seniority off.
            - 0–49: Weak match; critical requirements unmet.

            RULES
            - Ground every claim in the supplied CV and job text only.
            - Prefer exact skill/tool matches over vague synonym guesses.
            - If the posting requires a credential or years of experience the CV
              does not show, lower the score and say so in match_reasoning.
            - match_reasoning: 2–4 sentences — strengths first, then gaps.
            - company_name: extract if present; otherwise null.
            - Never invent company details or candidate experience.
        INSTRUCTIONS;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'match_score' => $schema->integer()->min(0)->max(100)->required(),
            'match_reasoning' => $schema->string()->required(),
            'company_name' => $schema->string()->nullable(),
        ];
    }
}
