<?php

declare(strict_types=1);

namespace Modules\Company\Infrastructure\Persistence\Repositories;

use App\Models\CompanyData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Company\Domain\Ports\CompanyRepositoryPort;

/**
 * Eloquent adapter for {@see CompanyRepositoryPort}, operating on the canonical
 * {@see CompanyData} model. No bulk/soft-delete methods: the singleton record is
 * never deleted or restored through the application.
 */
final class EloquentCompanyRepository implements CompanyRepositoryPort
{
    public function paginate(int $perPage): LengthAwarePaginator
    {
        return CompanyData::query()
            ->select([
                'id', 'uuid', 'name', 'company_name', 'email', 'phone', 'website',
                'address', 'address_2', 'logo_path', 'logo_white_path', 'mark_path',
                'signature_path', 'created_at', 'updated_at',
            ])
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getSingleton(): CompanyData
    {
        return CompanyData::query()->orderBy('id')->firstOrFail();
    }

    public function findByUuid(string $uuid): ?CompanyData
    {
        return CompanyData::query()->where('uuid', $uuid)->first();
    }

    public function update(CompanyData $company, array $attributes): CompanyData
    {
        $company->update($attributes);

        return $company->refresh();
    }
}
