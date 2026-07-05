<?php

declare(strict_types=1);

namespace Modules\Company\Domain\Ports;

use App\Models\CompanyData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Persistence contract for the Company module. Operates on the canonical
 * {@see CompanyData} model (reused — never duplicated as a second model).
 *
 * The module follows a settings-singleton pattern: there is exactly one company
 * record (seeded, never created or deleted through the UI). `getSingleton()`
 * resolves it for asset mutations that carry no route key.
 */
interface CompanyRepositoryPort
{
    /**
     * Paginated listing for the admin DataTable (always 0–1 rows).
     *
     * @return LengthAwarePaginator<int, CompanyData>
     */
    public function paginate(int $perPage): LengthAwarePaginator;

    /**
     * The single company record. Throws when the table has not been seeded.
     */
    public function getSingleton(): CompanyData;

    public function findByUuid(string $uuid): ?CompanyData;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(CompanyData $company, array $attributes): CompanyData;
}
