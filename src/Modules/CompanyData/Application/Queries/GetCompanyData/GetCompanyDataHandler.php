<?php

declare(strict_types=1);

namespace Modules\CompanyData\Application\Queries\GetCompanyData;

use Modules\CompanyData\Application\Queries\ReadModels\CompanyDataReadModel;
use Modules\CompanyData\Domain\Ports\CompanyDataRepositoryPort;
use Modules\CompanyData\Domain\ValueObjects\UserId;
use Illuminate\Support\Facades\Cache;

final readonly class GetCompanyDataHandler
{
    public function __construct(
        private CompanyDataRepositoryPort $repository,
    ) {
    }

    public function handle(GetCompanyDataQuery $query): CompanyDataReadModel
    {
        $cacheKey = "company_data_{$query->userUuid}";
        $ttl = 60 * 60; // 1 hour

        try {
            return Cache::tags(['company_data'])->remember($cacheKey, $ttl, fn() => $this->fetchReadModel($query));
        } catch (\Exception) {
            return Cache::remember($cacheKey, $ttl, fn() => $this->fetchReadModel($query));
        }
    }

    private function fetchReadModel(GetCompanyDataQuery $query): CompanyDataReadModel
    {
        $companyData = $this->repository->findByUserId(new UserId($query->userUuid));

        if (null === $companyData) {
            abort(404, "Company data for user [{$query->userUuid}] not found.");
        }

        return new CompanyDataReadModel(
            uuid: $companyData->id->value,
            userUuid: $companyData->userId->value,
            companyName: $companyData->companyName,
            email: $companyData->email,
            phone: $companyData->phone,
            address: $companyData->address,
            socialLinks: $companyData->socialLinks->toArray(),
            coordinates: $companyData->coordinates->toArray(),
            status: $companyData->status->value,
            signatureUrl: $companyData->signaturePath,
            createdAt: $companyData->createdAt ?? '',
            updatedAt: $companyData->updatedAt ?? '',
        );
    }
}
