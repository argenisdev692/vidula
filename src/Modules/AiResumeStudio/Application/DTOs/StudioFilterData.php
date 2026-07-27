<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Application\DTOs;

use Illuminate\Validation\Rule;
use Modules\AiResumeStudio\Domain\Enums\StudioMode;
use Shared\Application\DTOs\SoftDeleteFilterData;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
final class StudioFilterData extends SoftDeleteFilterData
{
    public function __construct(
        ?string $search = null,
        ?string $status = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        public ?string $mode = null,
        public ?string $runUuid = null,
    ) {
        parent::__construct($search, $status, $dateFrom, $dateTo);
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            ...self::baseRules(),
            'status' => ['nullable', 'string', 'in:active,suspended'],
            'mode' => ['nullable', 'string', Rule::in(StudioMode::values())],
            'run_uuid' => ['nullable', 'uuid'],
        ];
    }
}
