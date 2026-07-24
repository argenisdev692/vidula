<?php

declare(strict_types=1);

namespace Modules\Appointment\Application\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Appointment\Application\Listeners\Concerns\ResolvesCompanyRecipient;
use Modules\Appointment\Application\Queries\GetAppointmentHandler;
use Modules\Appointment\Domain\Events\AppointmentBooked;
use Modules\Appointment\Infrastructure\Mail\NewLeadMail;
use Modules\Company\Domain\Ports\CompanyRepositoryPort;
use Shared\Infrastructure\Mail\MailInterface;

/**
 * Notifies the company / super-admin inbox as soon as a brand-new lead is
 * captured (the client gets a separate acknowledgement via
 * {@see SendAppointmentReceivedEmailListener}). Queued — outbound mail must
 * never block the public booking request. The destination address is resolved
 * through {@see CompanyRepositoryPort} (a Domain Port, cross-module ACL) rather
 * than an Infrastructure branding helper, keeping this Application-layer
 * listener free of concrete Infrastructure imports.
 */
final class SendNewLeadAdminEmailListener implements ShouldQueue
{
    use ResolvesCompanyRecipient;

    public string $queue = 'emails';

    public function __construct(
        private GetAppointmentHandler $appointments,
        private CompanyRepositoryPort $companies,
        private MailInterface $mail,
    ) {}

    public function handle(AppointmentBooked $event): void
    {
        $appointment = $this->appointments->handle($event->uuid);

        $this->mail->send($this->companyRecipient($this->companies), new NewLeadMail($appointment));
    }
}
