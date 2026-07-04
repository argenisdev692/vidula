<?php

declare(strict_types=1);

namespace Modules\Users\Application\DTOs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Shared list/export filter — consumed by both ListUsersHandler and
 * UserExportController via the single `User::scopeApplyFilters()` (DRY).
 *
 * `status`: pending | active | suspended (soft-deleted). The date range filters
 * on `created_at`.
 */
#[MapInputName(SnakeCaseMapper::class)]
final class UserFilterData extends Data
{
    public function __construct(
        public ?string $search = null,
        public ?string $status = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:pending,active,suspended'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }
}
