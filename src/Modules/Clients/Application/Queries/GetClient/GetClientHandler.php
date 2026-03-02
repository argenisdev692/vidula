<?php

declare(strict_types=1);

namespace Modules\Clients\Application\Queries\GetClient;

use Modules\Clients\Application\Queries\ReadModels\ClientReadModel;
use Modules\Clients\Domain\Exceptions\ClientNotFoundException;
use Modules\Clients\Domain\Ports\ClientRepositoryPort;
use Modules\Clients\Domain\ValueObjects\UserId;
use Illuminate\Support\Facades\Cache;

final readonly class GetClientHandler
{
    public function __construct(
        private ClientRepositoryPort $repository
    ) {
    }

    public function handle(GetClientQuery $query): ClientReadModel
    {
        $cacheKey = "client_{$query->userUuid}";
        $ttl = 60 * 60; // 1 hour

        return Cache::remember($cacheKey, $ttl, function () use ($query) {
            $client = $this->repository->findByUserId(new UserId($query->userUuid));

            if (null === $client) {
                throw ClientNotFoundException::forUser($query->userUuid);
            }

            return new ClientReadModel(
                uuid: $client->id->value,
                userUuid: $client->userId->value,
                companyName: $client->companyName,
                email: $client->email,
                phone: $client->phone,
                address: $client->address,
                socialLinks: $client->socialLinks?->toArray() ?? [],
                coordinates: $client->coordinates?->toArray() ?? [],
                createdAt: $client->createdAt,
                updatedAt: $client->updatedAt,
                deletedAt: $client->deletedAt
            );
        });
    }
}
