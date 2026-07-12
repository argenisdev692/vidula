<?php

declare(strict_types=1);

namespace Modules\Appointment\Application\Commands;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\DB;
use Modules\Appointment\Domain\Ports\AppointmentRepositoryPort;

/**
 * Soft-deletes a single lead by UUID. Authorization
 * (permission:DELETE_APPOINTMENTS) is enforced at the route.
 */
final readonly class DeleteAppointmentHandler
{
    public function __construct(
        private AppointmentRepositoryPort $appointments,
        private Cache $cache,
    ) {}

    public function handle(string $uuid): bool
    {
        $result = DB::transaction(fn () => $this->appointments->softDelete($uuid));

        $this->cache->forget("appointment_{$uuid}");

        return $result;
    }
}
