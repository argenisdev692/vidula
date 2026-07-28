<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Infrastructure\Ai;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Stage 1a — CV judge / audit only (no rewrite).
 * Distills recruiter 10s-scan + XYZ auditor prompts from specs/003 prompt.md.
 */
final class CvJudgeAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
            You are a senior Fortune-500 recruiter and ATS specialist auditing a
            SOURCE CV before any rewrite. Do NOT rewrite the resume.

            YOUR JOB
            1) Run a 10-second recruiter scan: what stands out, what is
               forgettable, what looks weak or generic.
            2) Find keyword / competency gaps vs TARGET ROLE, TARGETING brief,
               optional JOB DESCRIPTION, and GITHUB evidence when provided.
            3) Score experience bullets against Google XYZ
               (Achieved X, measured by Y, by doing Z). List lines that lack
               a measurable Y in xyz_gaps.
            4) Ask targeted metric_questions ONLY where honest estimates would
               unlock stronger bullets. Prefer ranges and scope over fake
               precision. Never invent numbers yourself.

            HARD RULES
            - Use ONLY facts in SOURCE CV / briefs / GitHub. Never invent
              employers, titles, degrees, tools, or metrics.
            - metric_questions: 0–6 short, answerable questions. Each needs a
              stable id (q1, q2…). If the CV is already well quantified or no
              honest estimate is possible, return an empty array.
            - weak_lines: paraphrase the weak lines — do not dump the whole CV.
            - target_job_title: best inferred title from briefs / JD / CV.

            OUTPUT LANGUAGE
            - Write audit strings in the same language as the SOURCE CV unless
              an OUTPUT LANGUAGE block in the user prompt overrides it.
        INSTRUCTIONS;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'target_job_title' => $schema->string()->required(),
            'strengths' => $schema->array()->items($schema->string())->required(),
            'improvements' => $schema->array()->items($schema->string())->required(),
            'keyword_gaps' => $schema->array()->items($schema->string())->required(),
            'weak_lines' => $schema->array()->items($schema->string())->required(),
            'xyz_gaps' => $schema->array()->items($schema->string())->required(),
            'metric_questions' => $schema->array()->items(
                $schema->object([
                    'id' => $schema->string()->required(),
                    'question' => $schema->string()->required(),
                    'related_bullet' => $schema->string()->nullable(),
                ]),
            )->required(),
        ];
    }
}
