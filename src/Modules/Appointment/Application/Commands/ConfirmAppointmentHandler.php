<?php

declare(strict_types=1);

namespace Modules\Appointment\Application\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Appointment\Application\DTOs\ConfirmAppointmentData;
use Modules\Appointment\Domain\Events\AppointmentConfirmed;
use Modules\Appointment\Domain\Exceptions\PastDateSchedulingException;
use Modules\Appointment\Domain\Exceptions\SlotUnavailableException;
use Modules\Appointment\Domain\Ports\AppointmentRepositoryPort;
use Modules\Appointment\Domain\Services\AppointmentScheduler;
use Modules\Appointment\Domain\ValueObjects\MeetingStatus;
use Modules\Appointment\Infrastructure\Persistence\Eloquent\Models\AppointmentEloquentModel;

/**
 * Confirms a lead's meeting. `scheduled_at` must be present (either from the
 * original booking or an admin-supplied override) and is re-validated by
 * {@see AppointmentScheduler} before the meeting is marked Confirmed — time may
 * have passed, or the slot may have been taken, between capture and review.
 * Dispatches {@see AppointmentConfirmed} for the client-facing email.
 */
final readonly class ConfirmAppointmentHandler
{
    public function __construct(
        private AppointmentRepositoryPort $appointments,
        private AppointmentScheduler $scheduler,
        private Cache $cache,
    ) {}

    #[\NoDiscard]
    public function handle(AppointmentEloquentModel $appointment, ConfirmAppointmentData $data): AppointmentEloquentModel
    {
        $scheduledAt = $data->scheduledAt !== null
            ? CarbonImmutable::parse($data->scheduledAt)
            : $appointment->scheduled_at;

        if ($scheduledAt === null) {
            throw ValidationException::withMessages(['scheduled_at' => ['A date and time is required to confirm this appointment.']]);
        }

        try {
            $this->scheduler->assertBookable($scheduledAt, $appointment->uuid);
        } catch (PastDateSchedulingException|SlotUnavailableException $e) {
            throw ValidationException::withMessages(['scheduled_at' => [$e->getMessage()]]);
        }

        $confirmed = DB::transaction(fn () => $this->appointments->update($appointment, [
            'scheduled_at' => $scheduledAt,
            'meeting_status' => MeetingStatus::Confirmed,
        ]));

        $this->cache->forget("appointment_{$confirmed->uuid}");
        event(new AppointmentConfirmed($confirmed->uuid));

        return $confirmed;
    }
}
