<?php

declare(strict_types=1);

namespace Modules\CompanyData\Application\Queries\ListCompanyData;

use Modules\CompanyData\Application\Queries\ReadModels\CompanyDataReadModel;
use Modules\CompanyData\Domain\Ports\CompanyDataRepositoryPort;
use Illuminate\Support\Facades\Cache;

final readonly class ListCompanyDataHandler
{
    public function __construct(
        private CompanyDataRepositoryPort $repository
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

        return Cache::remember($cacheKey, $ttl, function () use ($filters) {
            $result = $this->repository->findAllPaginated(
                filters: $filters->toArray(),
                page: $filters->page,
                perPage: $filters->perPage
            );

            $mapped = array_map(
                fn($companyData) => new CompanyDataReadModel(
                    uuid: $companyData->id->value,
                    userUuid: $companyData->userId->value,
                    companyName: $companyData->companyName,
                    email: $companyData->email,
                    phone: $companyData->phone,
                    address: $companyData->address,
                    socialLinks: $companyData->socialLinks->toArray(),
                    coordinates: $companyData->coordinates->toArray(),
                    status: $companyData->status->value,
                    signatureUrl: $companyData->signaturePath
                ),
                $result['data']
            );

            // Wrap pagination into `meta` to match frontend PaginatedResponse<T>
            return [
                'data' => $mapped,
                'meta' => [
                    'total' => $result['total'],
                    'perPage' => $result['perPage'],
                    'currentPage' => $result['currentPage'],
                    'lastPage' => $result['lastPage'],
                ],
            ];
        });
    }
}
