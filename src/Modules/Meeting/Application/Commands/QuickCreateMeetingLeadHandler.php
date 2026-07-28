<?php

declare(strict_types=1);

namespace Modules\Meeting\Application\Commands;

use Modules\Appointment\Application\Commands\CreateAppointmentHandler;
use Modules\Appointment\Application\DTOs\AppointmentData;
use Modules\Meeting\Application\DTOs\QuickCreateMeetingLeadData;
use Modules\Meeting\Domain\ValueObjects\AttendeeType;

/**
 * Creates a minimal Appointment (lead) from the meeting attendee picker and
 * returns the same `{type, uuid, label}` shape as SearchAttendeesHandler so the
 * UI can chip it immediately.
 */
final readonly class QuickCreateMeetingLeadHandler
{
    public function __construct(
        private CreateAppointmentHandler $createAppointment,
    ) {}

    /**
     * @return array{type: string, uuid: string, label: string}
     */
    #[\NoDiscard]
    public function handle(QuickCreateMeetingLeadData $data): array
    {
        $appointment = $this->createAppointment->handle(new AppointmentData(
            firstName: $data->firstName,
            lastName: $data->lastName,
            clientType: 'individual',
            companyName: null,
            projectType: null,
            email: $data->email,
            phone: $data->phone,
            address: null,
            address2: null,
            zipCode: null,
            city: null,
            state: null,
            country: null,
            countryCode: null,
            latitude: null,
            longitude: null,
            smsConsent: false,
            notes: 'Quick-created as a meeting attendee.',
            owner: null,
        ));

        $name = trim("{$appointment->first_name} {$appointment->last_name}");

        return [
            'type' => AttendeeType::Lead->value,
            'uuid' => $appointment->uuid,
            'label' => "{$name} · {$appointment->email}",
        ];
    }
}
