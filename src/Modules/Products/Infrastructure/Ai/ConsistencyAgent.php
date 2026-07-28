<?php

declare(strict_types=1);

namespace Modules\Products\Infrastructure\Ai;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * The pipeline's `verifying` stage: compares the operator's seed index
 * against what was actually generated and reports drift.
 *
 * It grades titles only — cheap, deterministic enough to trust, and drift at
 * the title level is exactly what betrays a hallucinated or merged topic.
 * The verdict never triggers an automatic rewrite: flagged topics land in
 * `needs_review` for a human (spec FR-13).
 */
final class ConsistencyAgent implements Agent, Conversational, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
            You are a curriculum auditor. You are given the SEED TOPICS an
            operator committed to teaching and the GENERATED TOPICS that were
            actually produced. Report whether the generated set still honours
            the seed.

            === RULES ===
            - Match on meaning, not on characters. Translations, singular vs
              plural, punctuation, numbering and trailing durations are NOT
              drift.
            - Drift IS: a topic replaced by a different subject, two seed
              topics merged into one, a topic narrowed or widened enough that a
              student would not get what the index promised.
            - missing_titles: seed titles with no generated counterpart at all.
              Use the seed title verbatim so it can be matched programmatically.
            - drifted_topics: pairs where a counterpart exists but the subject
              moved. `seed_title` must be the seed title verbatim.
            - coverage_score: 0-100, the share of the seed's intent that
              survived. Grade honestly — this number gates a human review.
            - consistent: true only when there are no missing titles, no
              drifted topics, and coverage_score is at least 90.
            - summary: two sentences maximum, plain and specific.
            INSTRUCTIONS;
    }

    /**
     * @return Message[]
     */
    public function messages(): iterable
    {
        return [];
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'consistent' => $schema->boolean()->required(),
            'coverage_score' => $schema->integer()->min(0)->max(100)->required(),
            'missing_titles' => $schema->array()->items($schema->string())->required(),
            'drifted_topics' => $schema->array()
                ->items($schema->object(fn ($schema) => [
                    'seed_title' => $schema->string()->required(),
                    'generated_title' => $schema->string()->required(),
                    'reason' => $schema->string()->required(),
                ]))
                ->required(),
            'summary' => $schema->string()->required(),
        ];
    }
}
