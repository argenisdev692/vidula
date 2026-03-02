<?php

declare(strict_types=1);

namespace Modules\Clients\Domain\Ports;

use Modules\Clients\Domain\Entities\Client;
use Modules\Clients\Domain\ValueObjects\ClientId;
use Modules\Clients\Domain\ValueObjects\UserId;

/**
 * ClientRepositoryPort
 */
interface ClientRepositoryPort
{
    public function findById(ClientId $id): ?Client;

    public function findByUserId(UserId $userId): ?Client;

    public function save(Client $client): void;

    public function delete(ClientId $id): void;

    public function restore(ClientId $id): void;

    /**
     * @param array<string, mixed> $filters
     */
    public function findAllPaginated(array $filters = [], int $page = 1, int $perPage = 15): array;
}
