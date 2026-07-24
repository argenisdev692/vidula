<?php

declare(strict_types=1);

namespace Modules\Meeting\Infrastructure\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Meeting\Infrastructure\Persistence\Eloquent\Models\MeetingEloquentModel;

final class MeetingInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly MeetingEloquentModel $meeting,
        public readonly string $attendeeName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "You're invited: {$this->meeting->title}");
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.meetings.invitation',
            with: ['meeting' => $this->meeting, 'attendeeName' => $this->attendeeName],
        );
    }
}
