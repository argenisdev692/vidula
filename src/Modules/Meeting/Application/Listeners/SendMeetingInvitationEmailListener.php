<?php

declare(strict_types=1);

namespace Modules\Meeting\Application\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Meeting\Domain\Events\MeetingScheduled;
use Modules\Meeting\Domain\Ports\MeetingRepositoryPort;
use Modules\Meeting\Infrastructure\Attendees\AttendeeEmailResolver;
use Modules\Meeting\Infrastructure\Mail\MeetingInvitationMail;
use Shared\Infrastructure\Mail\MailInterface;

final readonly class SendMeetingInvitationEmailListener implements ShouldQueue
{
    public string $queue = 'emails';

    public function __construct(
        private MeetingRepositoryPort $meetings,
        private MailInterface $mail,
    ) {}

    public function handle(MeetingScheduled $event): void
    {
        $meeting = $this->meetings->findByUuid($event->uuid);

        if ($meeting === null) {
            return;
        }

        foreach (AttendeeEmailResolver::resolve($meeting->attendees) as $attendee) {
            if ($attendee['email'] === '' || $attendee['email'] === null) {
                continue;
            }
            $this->mail->send($attendee['email'], new MeetingInvitationMail($meeting, $attendee['name']));
        }
    }
}
