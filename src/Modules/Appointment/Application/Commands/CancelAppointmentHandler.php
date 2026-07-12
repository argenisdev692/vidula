<?php

declare(strict_types=1);

namespace Modules\Appointment\Application\Commands;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\DB;
use Modules\Appointment\Application\DTOs\CancelAppointmentData;
use Modules\Appointment\Domain\Events\AppointmentCancelled;
use Modules\Appointment\Domain\Ports\AppointmentRepositoryPort;
use Modules\Appointment\Domain\ValueObjects\MeetingStatus;
use Modules\Appointment\Infrastructure\Persistence\Eloquent\Models\AppointmentEloquentModel;

/**
 * Cancels a booked meeting. `scheduled_at` is left untouched (the cancelled
 * email needs to show the date that was cancelled); an optional reason is
 * appended to `notes` for the operator's own record.
 */
final readonly class CancelAppointmentHandler
{
    public function __construct(
        private AppointmentRepositoryPort $appointments,
        private Cache $cache,
    ) {}

    public function handle(AppointmentEloquentModel $appointment, CancelAppointmentData $data): AppointmentEloquentModel
    {
        $notes = $data->reason !== null
            ? trim($appointment->notes."\n[Cancelled] ".$data->reason)
            : $appointment->notes;

        $cancelled = DB::transaction(fn () => $this->appointments->update($appointment, [
            'meeting_status' => MeetingStatus::Cancelled,
            'notes' => $notes,
        ]));

        $this->cache->forget("appointment_{$cancelled->uuid}");
        event(new AppointmentCancelled($cancelled->uuid));

        return $cancelled;
    }
}
