<?php

declare(strict_types=1);

namespace Modules\CompanyData\Application\Queries\ListCompanyData;

use Modules\CompanyData\Application\Queries\ReadModels\CompanyDataReadModel;
use Modules\CompanyData\Domain\Ports\CompanyDataRepositoryPort;
use Illuminate\Support\Facades\Cache;

final readonly class ListCompanyDataHandler
{
    public function __construct(
        private CompanyDataRepositoryPort $repository,
    ) {
    }

    /**
     * @return array{data: list<CompanyDataReadModel>, meta: array{total: int, perPage: int, currentPage: int, lastPage: int}}
     */
    public function handle(ListCompanyDataQuery $query): array
    {
        $filters = $query->filters;
        $cacheKey = "company_data_list_" . md5(serialize($filters->toArray()));
        $ttl = 60 * 15;

        try {
            return Cache::tags(['company_data_list'])->remember($cacheKey, $ttl, fn() => $this->fetchPaginatedData($filters));
        } catch (\Exception) {
            return Cache::remember($cacheKey, $ttl, fn() => $this->fetchPaginatedData($filters));
        }
    }

    /**
     * @return array{data: list<CompanyDataReadModel>, meta: array{total: int, perPage: int, currentPage: int, lastPage: int}}
     */
    private function fetchPaginatedData(\Modules\CompanyData\Application\DTOs\CompanyDataFilterDTO $filters): array
    {
        $result = $this->repository->findAllPaginated(
            filters: $filters->toArray(),
            page: $filters->page,
            perPage: $filters->perPage,
        );

        $mapped = array_map(
            function ($companyData): CompanyDataReadModel {
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
            },
            $result['data'],
        );

        return [
            'data' => $mapped,
            'meta' => [
                'total' => $result['total'],
                'perPage' => $result['perPage'],
                'currentPage' => $result['currentPage'],
                'lastPage' => $result['lastPage'],
            ],
        ];
    }
}
