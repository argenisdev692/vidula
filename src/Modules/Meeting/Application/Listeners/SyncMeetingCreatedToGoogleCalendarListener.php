<?php

declare(strict_types=1);

namespace Modules\Meeting\Application\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Meeting\Application\Queries\GetMeetingHandler;
use Modules\Meeting\Domain\Events\MeetingScheduled;
use Modules\Meeting\Domain\Ports\GoogleCalendarSyncPort;
use Modules\Meeting\Domain\Ports\MeetingRepositoryPort;

/**
 * Runs on the `default` queue — this project's Horizon supervisor only
 * watches `default` (see `config/horizon.php`), not a per-feature queue name,
 * so this deliberately does NOT set a custom `$queue` (unlike some of
 * Appointment's mail listeners, which target an `emails` queue Horizon
 * doesn't currently watch — flagged separately, not fixed here).
 */
final readonly class SyncMeetingCreatedToGoogleCalendarListener implements ShouldQueue
{
    public function __construct(
        private GetMeetingHandler $meetings,
        private MeetingRepositoryPort $repository,
        private GoogleCalendarSyncPort $googleCalendar,
    ) {}

    public function handle(MeetingScheduled $event): void
    {
        $meeting = $this->meetings->handle($event->uuid);

        $googleEventId = $this->googleCalendar->createEvent($meeting);

        if ($googleEventId !== null) {
            $this->repository->update($meeting, ['google_event_id' => $googleEventId]);
        }
    }
}
