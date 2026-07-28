<?php

declare(strict_types=1);

namespace Modules\Clients\Application\Commands;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\DB;
use Modules\Clients\Application\Support\ClientCacheKeys;
use Modules\Clients\Domain\Ports\ClientRepositoryPort;
use Shared\Application\DTOs\BulkUuidsData;

final readonly class BulkDeleteClientsHandler
{
    public function __construct(
        private ClientRepositoryPort $clients,
        private Cache $cache,
    ) {}

    #[\NoDiscard]
    public function handle(BulkUuidsData $data): int
    {
        $count = DB::transaction(fn () => $this->clients->bulkSoftDeleteByUuid($data->uuids));

        foreach ($data->uuids as $uuid) {
            $this->cache->forget(ClientCacheKeys::client($uuid));
        }

        return $count;
    }
}
