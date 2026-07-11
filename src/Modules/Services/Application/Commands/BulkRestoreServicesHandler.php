<?php

declare(strict_types=1);

namespace Modules\Services\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Services\Domain\Ports\ServiceRepositoryPort;
use Modules\Services\Infrastructure\Cache\ServicePublicFeedCache;
use Shared\Application\DTOs\BulkUuidsData;

/**
 * Restores a set of soft-deleted services by UUID. Authorization
 * (permission:BULK_RESTORE_SERVICES) is enforced at the route.
 */
final readonly class BulkRestoreServicesHandler
{
    public function __construct(private ServiceRepositoryPort $services) {}

    public function handle(BulkUuidsData $data): int
    {
        $count = DB::transaction(fn () => $this->services->bulkRestoreByUuid($data->uuids));

        ServicePublicFeedCache::flush();

        return $count;
    }
}
