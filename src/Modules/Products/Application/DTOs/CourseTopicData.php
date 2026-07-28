<?php

declare(strict_types=1);

namespace Modules\Products\Application\DTOs;

use Modules\Products\Domain\Enums\ScriptStatus;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * A topic as it appears in the rendered course document.
 */
#[MapOutputName(SnakeCaseMapper::class)]
final class CourseTopicData extends Data
{
    /**
     * @param  list<array{type: string, title: string, url: string, snippet: string}>  $sources
     */
    public function __construct(
        public string $uuid,
        public string $title,
        public int $sortOrder,
        public ?string $intro = null,
        public ?string $body = null,
        public ?string $outro = null,
        public ?string $notes = null,
        public ?int $estimatedMinutes = null,
        public ?ScriptStatus $status = null,
        public array $sources = [],
    ) {}
}
