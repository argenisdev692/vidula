<?php

declare(strict_types=1);

namespace Modules\Meeting\Application\Queries;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Modules\Meeting\Application\DTOs\CalendarEventData;
use Modules\Meeting\Domain\Ports\AppointmentCalendarFeedPort;
use Modules\Meeting\Domain\Ports\MeetingRepositoryPort;
use Modules\Meeting\Infrastructure\Persistence\Eloquent\Models\MeetingEloquentModel;

/**
 * Combines Meeting's own range query with the read-only Appointment overlay
 * (plan.md §3) into one flat, chronologically sorted `CalendarEventData[]` —
 * the single feed `@fullcalendar/vue3` consumes.
 */
final readonly class GetMeetingCalendarFeedHandler
{
    public function __construct(
        private MeetingRepositoryPort $meetings,
        private AppointmentCalendarFeedPort $appointmentFeed,
    ) {}

    /**
     * @return Collection<int, CalendarEventData>
     */
    public function handle(CarbonInterface $from, CarbonInterface $to): Collection
    {
        $ownEvents = $this->meetings->between($from, $to)->map(self::toCalendarEvent(...));

        return $ownEvents
            ->concat($this->appointmentFeed->between($from, $to))
            ->sortBy('start')
            ->values();
    }

    private static function toCalendarEvent(MeetingEloquentModel $meeting): CalendarEventData
    {
        return new CalendarEventData(
            uuid: $meeting->uuid,
            title: $meeting->title,
            start: $meeting->starts_at->toIso8601String(),
            end: $meeting->ends_at->toIso8601String(),
            source: 'meeting',
            status: $meeting->status->value,
            url: route('meetings.show', $meeting->uuid),
        );
    }
}
