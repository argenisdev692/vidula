<?php

declare(strict_types=1);

namespace Modules\Products\Application\DTOs;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * A classroom session or a video block from the seed markdown.
 */
#[MapOutputName(SnakeCaseMapper::class)]
final class SeedSessionData extends Data
{
    /**
     * @param  list<SeedTopicData>  $topics
     */
    public function __construct(
        public int $sessionNumber,
        public string $title,
        #[DataCollectionOf(SeedTopicData::class)]
        public array $topics,
    ) {}
}
