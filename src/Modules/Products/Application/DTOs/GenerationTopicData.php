<?php

declare(strict_types=1);

namespace Modules\Products\Application\DTOs;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * A persisted topic row the `generating` stage iterates over, carrying its
 * session context so each prompt knows where the topic sits in the course.
 */
#[MapOutputName(SnakeCaseMapper::class)]
final class GenerationTopicData extends Data
{
    public function __construct(
        public string $uuid,
        public string $title,
        public int $sessionNumber,
        public string $sessionTitle,
        public int $sortOrder,
        public ?string $description = null,
    ) {}
}
