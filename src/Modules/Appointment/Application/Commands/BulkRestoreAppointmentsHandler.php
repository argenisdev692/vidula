<?php

declare(strict_types=1);

namespace Modules\Appointment\Application\Commands;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\DB;
use Modules\Appointment\Domain\Ports\AppointmentRepositoryPort;
use Shared\Application\DTOs\BulkUuidsData;

/**
 * Restores a set of soft-deleted leads by UUID. Authorization
 * (permission:BULK_RESTORE_APPOINTMENTS) is enforced at the route.
 */
final readonly class BulkRestoreAppointmentsHandler
{
    public function __construct(
        private AppointmentRepositoryPort $appointments,
        private Cache $cache,
    ) {}

    public function handle(BulkUuidsData $data): int
    {
        $count = DB::transaction(fn () => $this->appointments->bulkRestoreByUuid($data->uuids));

        foreach ($data->uuids as $uuid) {
            $this->cache->forget("appointment_{$uuid}");
        }

        return $count;
    }
}
