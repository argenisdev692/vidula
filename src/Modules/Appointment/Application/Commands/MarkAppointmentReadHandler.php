<?php

declare(strict_types=1);

namespace Modules\Appointment\Application\Commands;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\DB;
use Modules\Appointment\Domain\Ports\AppointmentRepositoryPort;

/**
 * Marks a single lead as read (`readed = true`). Mirrors ContactSupport's
 * mark-as-read action. Authorization (permission:UPDATE_APPOINTMENTS) is
 * enforced at the route.
 */
final readonly class MarkAppointmentReadHandler
{
    public function __construct(
        private AppointmentRepositoryPort $appointments,
        private Cache $cache,
    ) {}

    public function handle(string $uuid): bool
    {
        $result = DB::transaction(fn () => $this->appointments->markAsRead($uuid));

        $this->cache->forget("appointment_{$uuid}");

        return $result;
    }
}
