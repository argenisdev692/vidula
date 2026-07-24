<?php

declare(strict_types=1);

namespace Modules\Appointment\Application\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Appointment\Application\Queries\GetAppointmentHandler;
use Modules\Appointment\Domain\Events\AppointmentBooked;
use Modules\Appointment\Infrastructure\Mail\AppointmentReceivedMail;
use Shared\Infrastructure\Mail\MailInterface;

/**
 * Acknowledges a brand-new public booking to the CLIENT (their own appointment
 * email). Runs on the same {@see AppointmentBooked} event as the company-inbox
 * notification, but a separate listener keeps each recipient's concern isolated
 * (SRP). Queued — outbound mail never blocks the public booking request.
 */
final class SendAppointmentReceivedEmailListener implements ShouldQueue
{
    public string $queue = 'emails';

    public function __construct(
        private GetAppointmentHandler $appointments,
        private MailInterface $mail,
    ) {}

    public function handle(AppointmentBooked $event): void
    {
        $appointment = $this->appointments->handle($event->uuid);

        $this->mail->send($appointment->email, new AppointmentReceivedMail($appointment));
    }
}
