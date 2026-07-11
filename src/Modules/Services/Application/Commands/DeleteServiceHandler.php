<?php

declare(strict_types=1);

namespace Modules\Services\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Services\Domain\Ports\ServiceRepositoryPort;
use Modules\Services\Infrastructure\Cache\ServicePublicFeedCache;

/**
 * Soft-deletes a single service by UUID. Authorization
 * (permission:DELETE_SERVICES) is enforced at the route.
 */
final readonly class DeleteServiceHandler
{
    public function __construct(private ServiceRepositoryPort $services) {}

    public function handle(string $uuid): bool
    {
        $deleted = DB::transaction(fn () => $this->services->softDelete($uuid));

        ServicePublicFeedCache::flush();

        return $deleted;
    }
}
