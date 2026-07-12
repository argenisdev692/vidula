<?php

declare(strict_types=1);

namespace Modules\Appointment\Application\Commands;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\DB;
use Modules\Appointment\Application\DTOs\AddFollowUpCallData;
use Modules\Appointment\Domain\Ports\AppointmentRepositoryPort;
use Modules\Appointment\Domain\ValueObjects\StatusLead;
use Modules\Appointment\Infrastructure\Persistence\Eloquent\Models\AppointmentEloquentModel;

/**
 * Appends a timestamped call note to the lead's `follow_up_calls` JSON log.
 * A `New` lead is auto-advanced to `Called` on its first logged attempt — the
 * only automatic pipeline transition in the module, everything else is an
 * explicit operator action.
 */
final readonly class AddFollowUpCallHandler
{
    public function __construct(
        private AppointmentRepositoryPort $appointments,
        private Cache $cache,
    ) {}

    public function handle(AppointmentEloquentModel $appointment, AddFollowUpCallData $data): AppointmentEloquentModel
    {
        $calls = [
            ...($appointment->follow_up_calls ?? []),
            ['at' => now()->toIso8601String(), 'note' => $data->note],
        ];

        $updated = DB::transaction(fn () => $this->appointments->update($appointment, [
            'follow_up_calls' => $calls,
            'status_lead' => $appointment->status_lead === null || $appointment->status_lead === StatusLead::New
                ? StatusLead::Called
                : $appointment->status_lead,
        ]));

        $this->cache->forget("appointment_{$updated->uuid}");

        return $updated;
    }
}
