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
 * Admin-facing "new lead" notification. The listener already runs on a queued
 * worker, so this Mailable is sent synchronously from there — no `ShouldQueue`
 * on the Mailable itself (double-queueing would add nothing).
 */
final class NewLeadMail extends Mailable
{
    use SerializesModels;

    public function __construct(public readonly AppointmentEloquentModel $appointment) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New lead: '.$this->appointment->first_name.' '.$this->appointment->last_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.appointments.new-lead',
            with: [
                'appointment' => $this->appointment,
                'company' => CompanyProfile::data(),
            ],
        );
    }
}
