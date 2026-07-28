<?php

declare(strict_types=1);

namespace Modules\Students\Application\DTOs;

use Shared\Application\DTOs\SoftDeleteFilterData;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * List/export filter. Soft-delete `status` is active|suspended; optional
 * `student_status` filters the domain lifecycle column (DRAFT|ACTIVE|ARCHIVED).
 */
#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
final class StudentFilterData extends SoftDeleteFilterData
{
    public function __construct(
        ?string $search = null,
        ?string $status = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        public ?string $studentStatus = null,
        public ?bool $active = null,
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
            'student_status' => ['nullable', 'string', 'in:DRAFT,ACTIVE,ARCHIVED'],
            'active' => ['nullable', 'boolean'],
        ];
    }
}
