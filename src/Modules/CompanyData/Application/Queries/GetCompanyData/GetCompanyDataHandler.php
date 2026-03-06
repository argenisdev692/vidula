<?php

declare(strict_types=1);

namespace Modules\CompanyData\Application\Queries\GetCompanyData;

use Modules\CompanyData\Application\Queries\ReadModels\CompanyDataReadModel;
use Modules\CompanyData\Domain\Exceptions\CompanyDataNotFoundException;
use Modules\CompanyData\Domain\Ports\CompanyDataRepositoryPort;
use Modules\CompanyData\Domain\ValueObjects\CompanyDataId;
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
        $cacheKey = $query->companyUuid !== null
            ? "company_data_company_{$query->companyUuid}"
            : "company_data_user_{$query->userUuid}";

        $ttl = 60 * 60; // 1 hour

        try {
            return Cache::tags(['company_data'])->remember($cacheKey, $ttl, fn() => $this->fetchReadModel($query));
        } catch (\Exception) {
            return Cache::remember($cacheKey, $ttl, fn() => $this->fetchReadModel($query));
        }
    }

    private function fetchReadModel(GetCompanyDataQuery $query): CompanyDataReadModel
    {
        $companyData = $query->companyUuid !== null
            ? $this->repository->findById(new CompanyDataId($query->companyUuid))
            : $this->repository->findByUserId(new UserId((string) $query->userUuid));

        if (null === $companyData) {
            throw $query->companyUuid !== null
                ? CompanyDataNotFoundException::forId($query->companyUuid)
                : CompanyDataNotFoundException::forUser((string) $query->userUuid);
        }

        $socialLinks = $companyData->socialLinks->toArray();
        $coordinates = $companyData->coordinates->toArray();

        return new CompanyDataReadModel(
            uuid: $companyData->id->value,
            userUuid: $companyData->userId->value,
            companyName: $companyData->companyName,
            name: $companyData->name,
            email: $companyData->email,
            phone: $companyData->phone,
            address: $companyData->address,
            website: $socialLinks['website'] ?? null,
            facebookLink: $socialLinks['facebook'] ?? null,
            instagramLink: $socialLinks['instagram'] ?? null,
            linkedinLink: $socialLinks['linkedin'] ?? null,
            twitterLink: $socialLinks['twitter'] ?? null,
            socialLinks: $socialLinks,
            coordinates: $coordinates,
            latitude: $coordinates['latitude'],
            longitude: $coordinates['longitude'],
            status: $companyData->status->value,
            signatureUrl: $companyData->signaturePath,
            createdAt: $companyData->createdAt ?? '',
            updatedAt: $companyData->updatedAt ?? '',
            deletedAt: $companyData->deletedAt,
        );
    }
}
