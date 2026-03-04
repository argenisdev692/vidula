<?php

declare(strict_types=1);

namespace Modules\Clients\Application\Queries\ListClient;

use Modules\Clients\Application\Queries\ReadModels\ClientReadModel;
use Modules\Clients\Domain\Ports\ClientRepositoryPort;
use Illuminate\Support\Facades\Cache;

final readonly class ListClientHandler
{
    public function __construct(
        private ClientRepositoryPort $repository
    ) {
    }

    /**
     * @return array{data: list<ClientReadModel>, meta: array{total: int, perPage: int, currentPage: int, lastPage: int}}
     */
    public function handle(ListClientQuery $query): array
    {
        $filters = $query->filters;
        $cacheKey = "clients_list_" . md5(serialize($filters->toArray()));
        $ttl = 60 * 15;

        try {
            return Cache::tags(['clients_list'])->remember($cacheKey, $ttl, function () use ($filters) {
                return $this->fetchData($filters);
            });
        } catch (\Exception $e) {
            return Cache::remember($cacheKey, $ttl, function () use ($filters) {
                return $this->fetchData($filters);
            });
        }
    }

    private function fetchData($filters): array
    {
        $result = $this->repository->findAllPaginated(
            filters: $filters->toArray(),
            page: $filters->page,
            perPage: $filters->perPage
        );

        // Transform clients using pipe operator
        $mapped = $result['data']
            |> (fn($clients) => array_map(self::mapToReadModel(...), $clients));

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
    }

    /**
     * Map domain Client entity to ClientReadModel
     */
    private static function mapToReadModel($client): ClientReadModel
    {
        return new ClientReadModel(
            uuid: $client->id->value,
            userUuid: $client->userId->value,
            clientName: $client->clientName,
            email: $client->email,
            phone: $client->phone,
            address: $client->address,
            nif: $client->nif,
            socialLinks: $client->socialLinks?->toArray() ?? [],
            coordinates: $client->coordinates?->toArray() ?? [],
            createdAt: $client->createdAt,
            updatedAt: $client->updatedAt,
            deletedAt: $client->deletedAt
        );
    }
}
