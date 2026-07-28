<?php

declare(strict_types=1);

namespace Modules\Products\Application\DTOs;

use Modules\Products\Application\Commands\UpdateScriptHandler;
use Modules\Products\Domain\Enums\ScriptStatus;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Human review pass over one generated script (US-6). Every field is optional
 * so the operator can patch a single section; `null` means "leave untouched",
 * which {@see UpdateScriptHandler}
 * resolves via {@see self::toAttributes()}.
 */
#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
final class UpdateScriptData extends Data
{
    public function __construct(
        public ?string $intro = null,
        public ?string $body = null,
        public ?string $outro = null,
        public ?string $notes = null,
        public ?string $status = null,
        public ?int $estimatedMinutes = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'intro' => ['nullable', 'string', 'max:100000'],
            'body' => ['nullable', 'string', 'max:200000'],
            'outro' => ['nullable', 'string', 'max:100000'],
            'notes' => ['nullable', 'string', 'max:100000'],
            'status' => ['nullable', 'string', 'in:'.implode(',', array_map(
                static fn (ScriptStatus $case): string => $case->value,
                ScriptStatus::cases(),
            ))],
            'estimated_minutes' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ];
    }

    /**
     * Only the sections the operator actually sent, so a partial edit never
     * blanks the rest of the script.
     *
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return array_filter(
            [
                'intro' => $this->intro,
                'body' => $this->body,
                'outro' => $this->outro,
                'notes' => $this->notes,
                'status' => $this->status,
                'estimated_minutes' => $this->estimatedMinutes,
            ],
            static fn (mixed $value): bool => $value !== null,
        );
    }
}
