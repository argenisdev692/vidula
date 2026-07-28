<?php

declare(strict_types=1);

namespace Modules\Services\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Services\Domain\Ports\ServiceRepositoryPort;
use Modules\Services\Infrastructure\Cache\ServicePublicFeedCache;
use Shared\Application\DTOs\BulkUuidsData;

/**
 * Soft-deletes a set of services by UUID. Authorization
 * (permission:BULK_DELETE_SERVICES) is enforced at the route — never inside
 * this handler (no god-handler).
 */
final readonly class BulkDeleteServicesHandler
{
    public function __construct(private ServiceRepositoryPort $services) {}

    #[\NoDiscard]
    public function handle(BulkUuidsData $data): int
    {
        $count = DB::transaction(fn () => $this->services->bulkSoftDeleteByUuid($data->uuids));

        ServicePublicFeedCache::flush();

        return $count;
    }
}
