<?php

declare(strict_types=1);

namespace Modules\Meeting\Application\Listeners;

use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Meeting\Application\Queries\GetMeetingHandler;
use Modules\Meeting\Domain\Events\MeetingCancelled;
use Modules\Meeting\Infrastructure\Attendees\AttendeeEmailResolver;
use Modules\Meeting\Infrastructure\Mail\MeetingCancelledMail;

final readonly class SendMeetingCancelledEmailListener implements ShouldQueue
{
    public function __construct(
        private GetMeetingHandler $meetings,
        private Mailer $mailer,
    ) {}

    public function handle(MeetingCancelled $event): void
    {
        $meeting = $this->meetings->handle($event->uuid);

        foreach (AttendeeEmailResolver::resolve($meeting->attendees) as $attendee) {
            if ($attendee['email'] === '' || $attendee['email'] === null) {
                continue;
            }
            $this->mailer->to($attendee['email'])->send(new MeetingCancelledMail($meeting, $attendee['name']));
        }
    }
}
