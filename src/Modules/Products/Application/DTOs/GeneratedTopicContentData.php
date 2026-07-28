<?php

declare(strict_types=1);

namespace Modules\Products\Application\DTOs;

use Modules\Products\Domain\Enums\ScriptStatus;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * One generated script (video) or lesson note set (classroom).
 *
 * Video products fill intro/body/outro/notes; classroom products fill
 * body + notes and leave intro/outro null (clarify Q6). `sources` is persisted
 * verbatim as `sources_json` on BOTH the script and its topic so every claim
 * stays traceable to the Tavily result or Context7 snippet that grounded it.
 */
#[MapOutputName(SnakeCaseMapper::class)]
final class GeneratedTopicContentData extends Data
{
    /**
     * @param  list<string>  $keyPoints
     * @param  list<array{type: string, title: string, url: string, snippet: string}>  $sources
     */
    public function __construct(
        public ?string $intro,
        public string $body,
        public ?string $outro,
        public ?string $notes,
        public int $estimatedMinutes,
        public array $keyPoints,
        public array $sources,
        public ScriptStatus $status,
        public ?string $model = null,
    ) {}

    public function isGrounded(): bool
    {
        return $this->sources !== [];
    }
}
