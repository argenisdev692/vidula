<?php

declare(strict_types=1);

namespace Modules\Products\Application\DTOs;

use Modules\Products\Application\Services\SeedOutlineParser;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Whole parsed seed index produced by {@see SeedOutlineParser} — the input of
 * the pipeline's `parsing` stage and the source of truth for the content tree
 * that replaces the product's previous sessions/topics.
 */
#[MapOutputName(SnakeCaseMapper::class)]
final class SeedOutlineData extends Data
{
    /**
     * @param  list<SeedSessionData>  $sessions
     */
    public function __construct(
        #[DataCollectionOf(SeedSessionData::class)]
        public array $sessions,
    ) {}

    public function topicCount(): int
    {
        return array_sum(array_map(static fn (SeedSessionData $session): int => count($session->topics), $this->sessions));
    }

    /**
     * @return list<string>
     */
    public function topicTitles(): array
    {
        $titles = [];

        foreach ($this->sessions as $session) {
            foreach ($session->topics as $topic) {
                $titles[] = $topic->title;
            }
        }

        return $titles;
    }
}
