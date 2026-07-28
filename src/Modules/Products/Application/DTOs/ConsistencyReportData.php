<?php

declare(strict_types=1);

namespace Modules\Products\Application\DTOs;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Verdict of the `verifying` stage: does the generated content still cover
 * what the operator's seed index promised? Drifted topics are flagged
 * `needs_review` rather than regenerated — a human decides (spec FR-13).
 */
#[MapOutputName(SnakeCaseMapper::class)]
final class ConsistencyReportData extends Data
{
    /**
     * @param  list<string>  $missingTitles  Seed topics with no generated counterpart.
     * @param  list<array{seed_title: string, generated_title: string, reason: string}>  $driftedTopics
     */
    public function __construct(
        public bool $consistent,
        public int $coverageScore,
        public array $missingTitles,
        public array $driftedTopics,
        public string $summary,
    ) {}

    /**
     * Seed titles the pipeline should mark for review — drifted first, then
     * anything the model reported as missing entirely.
     *
     * @return list<string>
     */
    public function titlesNeedingReview(): array
    {
        $drifted = array_map(
            static fn (array $topic): string => $topic['seed_title'],
            $this->driftedTopics,
        );

        return array_values(array_unique([...$drifted, ...$this->missingTitles]));
    }
}
