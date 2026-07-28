<?php

declare(strict_types=1);

namespace Modules\Clients\Application\Queries;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Clients\Application\Support\ClientCacheKeys;
use Modules\Clients\Domain\Ports\ClientRepositoryPort;
use Modules\Clients\Infrastructure\Persistence\Eloquent\Models\ClientEloquentModel;

/**
 * Single-record lookup, cached 15 minutes per UUID (mirrors Invoices/Meetings).
 * Mutating handlers forget {@see ClientCacheKeys::client()}.
 */
final readonly class GetClientHandler
{
    public function __construct(
        private ClientRepositoryPort $clients,
        private Cache $cache,
    ) {}

    public function handle(string $uuid): ClientEloquentModel
    {
        return $this->cache->remember(
            ClientCacheKeys::client($uuid),
            now()->addMinutes(15),
            fn () => $this->clients->findByUuid($uuid)
                ?? throw (new ModelNotFoundException)->setModel(ClientEloquentModel::class, [$uuid]),
        );
    }
}
