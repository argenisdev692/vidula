<?php

declare(strict_types=1);

namespace Modules\Clients\Application\Commands;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\DB;
use Modules\Clients\Application\Support\ClientCacheKeys;
use Modules\Clients\Domain\Ports\ClientRepositoryPort;

final readonly class DeleteClientHandler
{
    public function __construct(
        private ClientRepositoryPort $clients,
        private Cache $cache,
    ) {}

    #[\NoDiscard]
    public function handle(string $uuid): bool
    {
        $result = DB::transaction(fn () => $this->clients->softDelete($uuid));

        $this->cache->forget(ClientCacheKeys::client($uuid));

        return $result;
    }
}
