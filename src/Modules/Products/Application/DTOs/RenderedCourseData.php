<?php

declare(strict_types=1);

namespace Modules\Products\Application\DTOs;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Output of the `rendering` stage: both documents already stored, plus the
 * in-memory bytes so the `packaging` stage can zip them without a second
 * round-trip to storage.
 */
#[MapOutputName(SnakeCaseMapper::class)]
final class RenderedCourseData extends Data
{
    public function __construct(
        public string $markdownPath,
        public string $pdfPath,
        public string $markdown,
        public string $pdfContents,
    ) {}
}
