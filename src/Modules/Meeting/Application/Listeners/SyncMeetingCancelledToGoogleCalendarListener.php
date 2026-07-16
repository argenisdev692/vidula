<?php

declare(strict_types=1);

namespace Modules\Meeting\Application\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Meeting\Application\Queries\GetMeetingHandler;
use Modules\Meeting\Domain\Events\MeetingCancelled;
use Modules\Meeting\Domain\Ports\GoogleCalendarSyncPort;

/**
 * Deletes the Google Calendar event on cancel rather than leaving a stale
 * event behind — a cancelled meeting has no further use for the calendar
 * entry (unlike Appointment, which keeps a `Cancelled` row visible for the
 * pipeline's audit trail; Meeting's Google event is a live-scheduling
 * artifact, not a record).
 */
final readonly class SyncMeetingCancelledToGoogleCalendarListener implements ShouldQueue
{
    public function __construct(
        private GetMeetingHandler $meetings,
        private GoogleCalendarSyncPort $googleCalendar,
    ) {}

    public function handle(MeetingCancelled $event): void
    {
        $this->googleCalendar->deleteEvent($this->meetings->handle($event->uuid));
    }
}
