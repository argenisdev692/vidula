<?php

declare(strict_types=1);

namespace Shared\Application\DTOs;

use Shared\Application\DTOs\Concerns\DateRangeFilterRules;
use Spatie\LaravelData\Data;

/**
 * Reusable base for soft-delete list/export filters — the common
 * `search` + `status` + `created_at` window shape shared by the Users, Roles,
 * Permissions and Blog-category filters.
 *
 * Mirrors the {@see BulkUuidsData} precedent: the shared shape lives here while
 * the module-specific bit (the `status` enum) stays local. Subclasses only
 * override {@see self::rules()}, spreading {@see self::baseRules()} and adding
 * their own `status` `in:` list, so no `scopeApplyFilters()` signature (which
 * type-hints the concrete subclass) has to change.
 */
abstract class SoftDeleteFilterData extends Data
{
    use DateRangeFilterRules;

    public function __construct(
        public ?string $search = null,
        public ?string $status = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
    ) {}

    /**
     * Common rules (search + created_at window). Subclasses spread this and add
     * their module-specific `status` rule.
     *
     * @return array<string, array<int, string>>
     */
    protected static function baseRules(): array
    {
        return static::dateRangeRules();
    }
}
