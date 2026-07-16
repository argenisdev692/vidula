<?php

declare(strict_types=1);

namespace Modules\Meeting\Application\Listeners;

use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Meeting\Application\Queries\GetMeetingHandler;
use Modules\Meeting\Domain\Events\MeetingScheduled;
use Modules\Meeting\Infrastructure\Attendees\AttendeeEmailResolver;
use Modules\Meeting\Infrastructure\Mail\MeetingInvitationMail;

final readonly class SendMeetingInvitationEmailListener implements ShouldQueue
{
    public function __construct(
        private GetMeetingHandler $meetings,
        private Mailer $mailer,
    ) {}

    public function handle(MeetingScheduled $event): void
    {
        $meeting = $this->meetings->handle($event->uuid);

        foreach (AttendeeEmailResolver::resolve($meeting->attendees) as $attendee) {
            if ($attendee['email'] === '' || $attendee['email'] === null) {
                continue;
            }
            $this->mailer->to($attendee['email'])->send(new MeetingInvitationMail($meeting, $attendee['name']));
        }
    }
}
