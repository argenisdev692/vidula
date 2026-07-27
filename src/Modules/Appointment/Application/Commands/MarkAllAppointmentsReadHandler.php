<?php

declare(strict_types=1);

namespace Modules\Appointment\Application\Commands;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\DB;
use Modules\Appointment\Domain\Ports\AppointmentRepositoryPort;

/**
 * Marks every unread lead as read in one shot — the navbar bell's "mark all
 * as read" action. Authorization (permission:UPDATE_APPOINTMENTS) is
 * enforced at the route. Invalidates per-UUID show caches so `readed` never
 * stays stale after a bulk mark.
 */
final readonly class MarkAllAppointmentsReadHandler
{
    public function __construct(
        private AppointmentRepositoryPort $appointments,
        private Cache $cache,
    ) {}

    public function handle(): int
    {
        $uuids = DB::transaction(fn (): array => $this->appointments->markAllAsRead());

        foreach ($uuids as $uuid) {
            $this->cache->forget("appointment_{$uuid}");
        }

        return count($uuids);
    }
}
