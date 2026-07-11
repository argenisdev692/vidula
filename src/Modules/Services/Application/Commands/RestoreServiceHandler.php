<?php

declare(strict_types=1);

namespace Modules\Services\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Services\Domain\Ports\ServiceRepositoryPort;
use Modules\Services\Infrastructure\Cache\ServicePublicFeedCache;

/**
 * Restores a soft-deleted service by UUID. Authorization
 * (permission:RESTORE_SERVICES) is enforced at the route.
 */
final readonly class RestoreServiceHandler
{
    public function __construct(private ServiceRepositoryPort $services) {}

    public function handle(string $uuid): bool
    {
        $restored = DB::transaction(fn () => $this->services->restore($uuid));

        ServicePublicFeedCache::flush();

        return $restored;
    }
}
