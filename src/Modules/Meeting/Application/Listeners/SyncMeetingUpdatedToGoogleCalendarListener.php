<?php

declare(strict_types=1);

namespace Modules\Meeting\Application\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Meeting\Application\Queries\GetMeetingHandler;
use Modules\Meeting\Domain\Events\MeetingUpdated;
use Modules\Meeting\Domain\Ports\GoogleCalendarSyncPort;

final readonly class SyncMeetingUpdatedToGoogleCalendarListener implements ShouldQueue
{
    public function __construct(
        private GetMeetingHandler $meetings,
        private GoogleCalendarSyncPort $googleCalendar,
    ) {}

    public function handle(MeetingUpdated $event): void
    {
        $this->googleCalendar->updateEvent($this->meetings->handle($event->uuid));
    }
}
