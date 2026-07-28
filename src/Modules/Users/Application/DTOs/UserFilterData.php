<?php

declare(strict_types=1);

namespace Modules\Users\Application\DTOs;

use Shared\Application\DTOs\SoftDeleteFilterData;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Shared list/export filter — consumed by both ListUsersHandler and
 * UserExportController via the single `User::scopeApplyFilters()` (DRY).
 * Inherits the search/status/date shape from {@see SoftDeleteFilterData}.
 *
 * `status`: pending | active | suspended (soft-deleted). The date range filters
 * on `created_at`. Sort follows BACKEND-PHP §5.2; `page`/`per_page` stay on the
 * request (capped in the controller).
 */
#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
final class UserFilterData extends SoftDeleteFilterData
{
    /** @var list<string> */
    public const array SORTABLE = ['created_at', 'first_name', 'last_name', 'email', 'username'];

    public function __construct(
        ?string $search = null,
        ?string $status = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        public string $sortField = 'created_at',
        public int $sortOrder = -1,
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
            'status' => ['nullable', 'string', 'in:pending,active,suspended'],
            'sort_field' => ['nullable', 'string', 'in:created_at,first_name,last_name,email,username'],
            'sort_order' => ['nullable', 'integer', 'in:1,-1'],
        ];
    }

    public function resolvedSortField(): string
    {
        return in_array($this->sortField, self::SORTABLE, true)
            ? $this->sortField
            : 'created_at';
    }

    public function resolvedSortDirection(): string
    {
        return $this->sortOrder === 1 ? 'asc' : 'desc';
    }
}
