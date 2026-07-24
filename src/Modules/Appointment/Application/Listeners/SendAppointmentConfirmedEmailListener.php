<?php

declare(strict_types=1);

namespace Modules\Appointment\Application\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Appointment\Application\Listeners\Concerns\ResolvesCompanyRecipient;
use Modules\Appointment\Application\Queries\GetAppointmentHandler;
use Modules\Appointment\Domain\Events\AppointmentConfirmed;
use Modules\Appointment\Infrastructure\Mail\AppointmentConfirmedMail;
use Modules\Company\Domain\Ports\CompanyRepositoryPort;
use Shared\Infrastructure\Mail\MailInterface;

final class SendAppointmentConfirmedEmailListener implements ShouldQueue
{
    use ResolvesCompanyRecipient;

    public string $queue = 'emails';

    public function __construct(
        private GetAppointmentHandler $appointments,
        private CompanyRepositoryPort $companies,
        private MailInterface $mail,
    ) {}

    public function handle(AppointmentConfirmed $event): void
    {
        $appointment = $this->appointments->handle($event->uuid);

        // Client is notified directly; the super-admin inbox gets a blind copy.
        $this->mail->send(
            $appointment->email,
            new AppointmentConfirmedMail($appointment),
            $this->companyRecipient($this->companies),
        );
    }
}
