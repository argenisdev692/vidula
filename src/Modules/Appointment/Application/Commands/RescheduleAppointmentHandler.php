<?php

declare(strict_types=1);

namespace Modules\Appointment\Application\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Appointment\Application\DTOs\RescheduleAppointmentData;
use Modules\Appointment\Domain\Events\AppointmentRescheduled;
use Modules\Appointment\Domain\Exceptions\PastDateSchedulingException;
use Modules\Appointment\Domain\Exceptions\SlotUnavailableException;
use Modules\Appointment\Domain\Ports\AppointmentRepositoryPort;
use Modules\Appointment\Domain\Services\AppointmentScheduler;
use Modules\Appointment\Domain\ValueObjects\MeetingStatus;
use Modules\Appointment\Infrastructure\Persistence\Eloquent\Models\AppointmentEloquentModel;

/**
 * Moves an appointment to a new date/time, preserving the previous one for the
 * "was rescheduled from X" email. The new timestamp goes through the same
 * {@see AppointmentScheduler} invariant chain as a first-time booking.
 */
final readonly class RescheduleAppointmentHandler
{
    public function __construct(
        private AppointmentRepositoryPort $appointments,
        private AppointmentScheduler $scheduler,
        private Cache $cache,
    ) {}

    #[\NoDiscard]
    public function handle(AppointmentEloquentModel $appointment, RescheduleAppointmentData $data): AppointmentEloquentModel
    {
        $scheduledAt = CarbonImmutable::parse($data->scheduledAt);

        try {
            $this->scheduler->assertBookable($scheduledAt, $appointment->uuid);
        } catch (PastDateSchedulingException|SlotUnavailableException $e) {
            throw ValidationException::withMessages(['scheduled_at' => [$e->getMessage()]]);
        }

        $rescheduled = DB::transaction(fn () => $this->appointments->update($appointment, [
            'previous_scheduled_at' => $appointment->scheduled_at,
            'scheduled_at' => $scheduledAt,
            'meeting_status' => MeetingStatus::Rescheduled,
        ]));

        $this->cache->forget("appointment_{$rescheduled->uuid}");
        event(new AppointmentRescheduled($rescheduled->uuid));

        return $rescheduled;
    }
}
