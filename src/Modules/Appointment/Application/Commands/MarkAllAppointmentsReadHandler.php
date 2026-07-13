<?php

declare(strict_types=1);

namespace Modules\Appointment\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Appointment\Domain\Ports\AppointmentRepositoryPort;

/**
 * Marks every unread lead as read in one shot — the navbar bell's "mark all
 * as read" action. Authorization (permission:UPDATE_APPOINTMENTS) is
 * enforced at the route.
 */
final readonly class MarkAllAppointmentsReadHandler
{
    public function __construct(private AppointmentRepositoryPort $appointments) {}

    public function handle(): int
    {
        return DB::transaction(fn () => $this->appointments->markAllAsRead());
    }
}
