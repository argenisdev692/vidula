<?php

declare(strict_types=1);

namespace Modules\Products\Application\DTOs;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * One topic as written by the operator in the seed markdown — the immutable
 * reference the consistency pass grades the generated content against.
 */
#[MapOutputName(SnakeCaseMapper::class)]
final class SeedTopicData extends Data
{
    public function __construct(
        public string $title,
        public int $sortOrder,
        public ?string $description = null,
    ) {}
}
