<?php

declare(strict_types=1);

namespace Modules\Meeting\Infrastructure\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Meeting\Infrastructure\Persistence\Eloquent\Models\MeetingEloquentModel;

final class MeetingCancelledMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public readonly MeetingEloquentModel $meeting,
        public readonly string $attendeeName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Cancelled: {$this->meeting->title}");
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.meetings.cancelled',
            with: ['meeting' => $this->meeting, 'attendeeName' => $this->attendeeName],
        );
    }
}
