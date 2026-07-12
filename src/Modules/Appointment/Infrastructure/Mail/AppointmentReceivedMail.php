<?php

declare(strict_types=1);

namespace Modules\Appointment\Infrastructure\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Appointment\Infrastructure\Persistence\Eloquent\Models\AppointmentEloquentModel;
use Shared\Infrastructure\Company\CompanyProfile;

/**
 * Client-facing acknowledgement sent the moment a new booking is captured on the
 * public landing page: "we received your request, it's pending confirmation".
 * The confirmed/rescheduled/cancelled mails cover the later lifecycle steps.
 */
final class AppointmentReceivedMail extends Mailable
{
    use SerializesModels;

    public function __construct(public readonly AppointmentEloquentModel $appointment) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'We received your appointment request');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.appointments.received',
            with: [
                'appointment' => $this->appointment,
                'company' => CompanyProfile::data(),
            ],
        );
    }
}
