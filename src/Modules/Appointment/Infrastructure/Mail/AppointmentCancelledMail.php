<?php

declare(strict_types=1);

namespace Modules\Appointment\Infrastructure\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Appointment\Infrastructure\Persistence\Eloquent\Models\AppointmentEloquentModel;
use Shared\Infrastructure\Company\CompanyProfile;

final class AppointmentCancelledMail extends Mailable
{
    use SerializesModels;

    public function __construct(public readonly AppointmentEloquentModel $appointment) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your appointment has been cancelled');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.appointments.cancelled',
            with: [
                'appointment' => $this->appointment,
                'company' => CompanyProfile::data(),
            ],
        );
    }
}
