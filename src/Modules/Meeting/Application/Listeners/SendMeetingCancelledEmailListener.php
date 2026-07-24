<?php

declare(strict_types=1);

namespace Modules\Meeting\Application\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Meeting\Application\Queries\GetMeetingHandler;
use Modules\Meeting\Domain\Events\MeetingCancelled;
use Modules\Meeting\Infrastructure\Attendees\AttendeeEmailResolver;
use Modules\Meeting\Infrastructure\Mail\MeetingCancelledMail;
use Shared\Infrastructure\Mail\MailInterface;

final readonly class SendMeetingCancelledEmailListener implements ShouldQueue
{
    public string $queue = 'emails';

    public function __construct(
        private GetMeetingHandler $meetings,
        private MailInterface $mail,
    ) {}

    public function handle(MeetingCancelled $event): void
    {
        $meeting = $this->meetings->handle($event->uuid);

        foreach (AttendeeEmailResolver::resolve($meeting->attendees) as $attendee) {
            if ($attendee['email'] === '' || $attendee['email'] === null) {
                continue;
            }
            $this->mail->send($attendee['email'], new MeetingCancelledMail($meeting, $attendee['name']));
        }
    }
}
