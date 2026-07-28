<?php

declare(strict_types=1);

namespace Modules\Products\Application\DTOs;

use Modules\Products\Domain\Enums\MaterialType;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * A pipeline-produced deliverable (course.md / course.pdf) about to be
 * persisted as a `product_materials` row. Always lands on the PRIVATE disk —
 * downloads go through an authorized route or a temporary signed URL.
 */
#[MapOutputName(SnakeCaseMapper::class)]
final class GeneratedMaterialData extends Data
{
    public function __construct(
        public string $title,
        public MaterialType $type,
        public string $disk,
        public string $path,
        public string $originalName,
        public string $mimeType,
        public int $sizeBytes,
        public int $sortOrder,
    ) {}
}
