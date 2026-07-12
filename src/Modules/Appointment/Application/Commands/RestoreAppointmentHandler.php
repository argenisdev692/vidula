<?php

declare(strict_types=1);

namespace Modules\Appointment\Application\Commands;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\DB;
use Modules\Appointment\Domain\Ports\AppointmentRepositoryPort;

/**
 * Restores a soft-deleted lead by UUID. Authorization
 * (permission:RESTORE_APPOINTMENTS) is enforced at the route.
 */
final readonly class RestoreAppointmentHandler
{
    public function __construct(
        private AppointmentRepositoryPort $appointments,
        private Cache $cache,
    ) {}

    public function handle(string $uuid): bool
    {
        $result = DB::transaction(fn () => $this->appointments->restore($uuid));

        $this->cache->forget("appointment_{$uuid}");

        return $result;
    }
}
