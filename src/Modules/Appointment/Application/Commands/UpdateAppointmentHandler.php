<?php

declare(strict_types=1);

namespace Modules\Appointment\Application\Commands;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\DB;
use Modules\Appointment\Application\DTOs\AppointmentData;
use Modules\Appointment\Domain\Ports\AppointmentRepositoryPort;
use Modules\Appointment\Infrastructure\Persistence\Eloquent\Models\AppointmentEloquentModel;

/**
 * Updates a lead's profile fields. Scheduling and pipeline state are owned by
 * the dedicated Confirm / Reschedule / Cancel / AddFollowUpCall actions, never
 * mass-assigned here. Authorization (permission:UPDATE_APPOINTMENTS) is
 * enforced at the route.
 */
final readonly class UpdateAppointmentHandler
{
    public function __construct(
        private AppointmentRepositoryPort $appointments,
        private Cache $cache,
    ) {}

    #[\NoDiscard]
    public function handle(AppointmentEloquentModel $appointment, AppointmentData $data): AppointmentEloquentModel
    {
        $email = $data->email |> trim(...) |> strtolower(...);

        $updated = DB::transaction(fn () => $this->appointments->update($appointment, [
            'first_name' => $data->firstName,
            'last_name' => $data->lastName,
            'client_type' => $data->clientType,
            'company_name' => $data->companyName,
            'project_type' => $data->projectType,
            'email' => $email,
            'phone' => $data->phone,
            'address' => $data->address,
            'address_2' => $data->address2,
            'zip_code' => $data->zipCode,
            'city' => $data->city,
            'state' => $data->state,
            'country' => $data->country,
            'country_code' => $data->countryCode,
            'latitude' => $data->latitude,
            'longitude' => $data->longitude,
            'sms_consent' => $data->smsConsent,
            'notes' => $data->notes,
            'owner' => $data->owner,
        ]));

        $this->cache->forget("appointment_{$updated->uuid}");

        return $updated;
    }
}
