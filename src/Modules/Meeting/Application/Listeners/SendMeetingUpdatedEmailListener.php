<?php

declare(strict_types=1);

namespace Modules\Meeting\Application\Listeners;

use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Meeting\Application\Queries\GetMeetingHandler;
use Modules\Meeting\Domain\Events\MeetingUpdated;
use Modules\Meeting\Infrastructure\Attendees\AttendeeEmailResolver;
use Modules\Meeting\Infrastructure\Mail\MeetingUpdatedMail;

final readonly class SendMeetingUpdatedEmailListener implements ShouldQueue
{
    public function __construct(
        private GetMeetingHandler $meetings,
        private Mailer $mailer,
    ) {}

    public function handle(MeetingUpdated $event): void
    {
        $meeting = $this->meetings->handle($event->uuid);

        foreach (AttendeeEmailResolver::resolve($meeting->attendees) as $attendee) {
            if ($attendee['email'] === '' || $attendee['email'] === null) {
                continue;
            }
            $this->mailer->to($attendee['email'])->queue(new MeetingUpdatedMail($meeting, $attendee['name']));
        }
    }
}
