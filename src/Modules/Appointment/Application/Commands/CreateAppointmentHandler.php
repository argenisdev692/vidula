<?php

declare(strict_types=1);

namespace Modules\Appointment\Application\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Appointment\Application\DTOs\AppointmentData;
use Modules\Appointment\Domain\Exceptions\PastDateSchedulingException;
use Modules\Appointment\Domain\Exceptions\SlotUnavailableException;
use Modules\Appointment\Domain\Ports\AppointmentRepositoryPort;
use Modules\Appointment\Domain\Services\AppointmentScheduler;
use Modules\Appointment\Domain\ValueObjects\StatusLead;
use Modules\Appointment\Infrastructure\Persistence\Eloquent\Models\AppointmentEloquentModel;

/**
 * Admin-created lead (e.g. a phone-in inquiry). No meeting time is required at
 * capture time — an operator schedules it later via Confirm. Authorization
 * (permission:CREATE_APPOINTMENTS) is enforced at the route.
 */
final readonly class CreateAppointmentHandler
{
    public function __construct(
        private AppointmentRepositoryPort $appointments,
        private AppointmentScheduler $scheduler,
    ) {}

    #[\NoDiscard]
    public function handle(AppointmentData $data, ?string $scheduledAt = null): AppointmentEloquentModel
    {
        $email = $data->email |> trim(...) |> strtolower(...);

        if ($scheduledAt !== null) {
            try {
                $this->scheduler->assertBookable(CarbonImmutable::parse($scheduledAt));
            } catch (PastDateSchedulingException|SlotUnavailableException $e) {
                throw ValidationException::withMessages(['scheduled_at' => [$e->getMessage()]]);
            }
        }

        return DB::transaction(fn () => $this->appointments->create([
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
            'status_lead' => StatusLead::New,
            'scheduled_at' => $scheduledAt,
        ]));
    }
}
