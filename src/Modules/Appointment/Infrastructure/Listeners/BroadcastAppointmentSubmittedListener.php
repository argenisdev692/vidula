<?php

declare(strict_types=1);

namespace Modules\Appointment\Infrastructure\Listeners;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Modules\Appointment\Application\Commands\BookAppointmentHandler;
use Modules\Appointment\Application\Queries\GetAppointmentHandler;
use Modules\Appointment\Domain\Events\AppointmentBooked;
use Modules\Appointment\Infrastructure\Broadcasting\AppointmentSubmitted;

/**
 * Pushes a navbar-bell Reverb event after a public booking. Lives in
 * Infrastructure so {@see BookAppointmentHandler}
 * never imports broadcasting (DIP). Sync (not queued) — the broadcast event
 * itself is already queued via {@see ShouldBroadcast}.
 */
final readonly class BroadcastAppointmentSubmittedListener
{
    public function __construct(private GetAppointmentHandler $appointments) {}

    public function handle(AppointmentBooked $event): void
    {
        broadcast(new AppointmentSubmitted($this->appointments->handle($event->uuid)));
    }
}
