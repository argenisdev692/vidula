<?php

declare(strict_types=1);

namespace Modules\Users\Application\DTOs;

use Shared\Application\DTOs\SoftDeleteFilterData;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Shared list/export filter — consumed by both ListUsersHandler and
 * UserExportController via the single `User::scopeApplyFilters()` (DRY).
 * Inherits the search/status/date shape from {@see SoftDeleteFilterData}.
 *
 * `status`: pending | active | suspended (soft-deleted). The date range filters
 * on `created_at`.
 */
#[MapInputName(SnakeCaseMapper::class)]
final class UserFilterData extends SoftDeleteFilterData
{
    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            ...self::baseRules(),
            'status' => ['nullable', 'string', 'in:pending,active,suspended'],
        ];
    }
}
