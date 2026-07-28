<?php

declare(strict_types=1);

namespace Modules\Products\Application\DTOs;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * A session/block as it appears in the rendered course document.
 */
#[MapOutputName(SnakeCaseMapper::class)]
final class CourseSessionData extends Data
{
    /**
     * @param  list<CourseTopicData>  $topics
     */
    public function __construct(
        public int $sessionNumber,
        public string $title,
        #[DataCollectionOf(CourseTopicData::class)]
        public array $topics,
    ) {}
}
