<?php

declare(strict_types=1);

namespace Modules\Meeting\Infrastructure\Appointment;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Modules\Appointment\Application\DTOs\AppointmentFilterData;
use Modules\Appointment\Domain\Ports\AppointmentRepositoryPort;
use Modules\Appointment\Infrastructure\Persistence\Eloquent\Models\AppointmentEloquentModel;
use Modules\Meeting\Application\DTOs\CalendarEventData;
use Modules\Meeting\Domain\Ports\AppointmentCalendarFeedPort;

/**
 * Binds Meeting's {@see AppointmentCalendarFeedPort} to Appointment's
 * EXISTING, unmodified {@see AppointmentRepositoryPort}. No new Appointment
 * code — `scheduledFrom`/`scheduledTo` on {@see AppointmentFilterData} already
 * covers exactly this read (research.md §"Summary — what changes in Plan").
 * Only non-cancelled, scheduled appointments (those with a `scheduled_at`)
 * are shown on the calendar — a lead with no confirmed time has nothing to
 * plot.
 */
final readonly class AppointmentCalendarFeedAdapter implements AppointmentCalendarFeedPort
{
    private const int MAX_PER_PAGE = 500;

    public function __construct(private AppointmentRepositoryPort $appointments) {}

    public function between(CarbonInterface $from, CarbonInterface $to): Collection
    {
        $filters = AppointmentFilterData::from([
            'scheduled_from' => $from->toDateString(),
            'scheduled_to' => $to->toDateString(),
        ]);

        return $this->appointments
            ->paginate($filters, self::MAX_PER_PAGE)
            ->getCollection()
            ->map(self::toCalendarEvent(...))
            ->values();
    }

    private static function toCalendarEvent(AppointmentEloquentModel $appointment): CalendarEventData
    {
        $start = $appointment->scheduled_at?->toIso8601String() ?? '';

        return new CalendarEventData(
            uuid: $appointment->uuid,
            title: trim("{$appointment->first_name} {$appointment->last_name}") ?: $appointment->email,
            start: $start,
            end: $start,
            source: 'appointment',
            status: $appointment->meeting_status?->value,
            url: route('appointments.show', $appointment->uuid),
        );
    }
}
