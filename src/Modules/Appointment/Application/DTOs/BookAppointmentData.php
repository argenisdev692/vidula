<?php

declare(strict_types=1);

namespace Modules\Appointment\Application\DTOs;

use Illuminate\Validation\Rule;
use Modules\Appointment\Application\Services\AppointmentServiceResolver;
use Modules\Appointment\Domain\Services\AppointmentScheduler;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * PUBLIC booking payload — the only surface the Astro landing page posts to
 * (`POST /api/appointments/public`). Unlike the admin {@see AppointmentData}, this DTO
 * requires `scheduled_at`: a first-time booking always requests a specific
 * date/time, validated by {@see AppointmentScheduler}
 * (not in the past, inside an open availability window, not already taken).
 *
 * `service_uuid` is the public identifier from `GET /api/services/public`
 * (`data[].uuid`). The API never accepts `services.id`; the handler resolves the
 * UUID to `appointments.service_id` server-side.
 */
#[MapInputName(SnakeCaseMapper::class)]
final class BookAppointmentData extends Data
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $clientType,
        public ?string $companyName,
        /** Active catalog service UUID (`GET /api/services/public` → `data[].uuid`). */
        public ?string $serviceUuid,
        public string $email,
        public ?string $phone,
        public ?string $address,
        #[MapInputName('address_2')]
        public ?string $address2,
        public ?string $zipCode,
        public ?string $city,
        public ?string $state,
        public ?string $country,
        public ?string $countryCode,
        public ?float $latitude,
        public ?float $longitude,
        public string $scheduledAt,
        public bool $smsConsent = false,
        public ?string $notes = null,
    ) {}

    /**
     * Normalize the email at the DTO boundary so both this public booking path
     * and the admin {@see AppointmentData} share one lowercased-email invariant
     * (the handler's `findActiveByEmail` lookup then matches the stored value).
     *
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    public static function prepareForPipeline(array $properties): array
    {
        if (isset($properties['email']) && is_string($properties['email'])) {
            $properties['email'] = mb_strtolower(trim($properties['email']));
        }

        return $properties;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'client_type' => ['required', Rule::in(['company', 'individual'])],
            'company_name' => ['nullable', 'string', 'max:255', 'required_if:client_type,company'],
            'service_uuid' => AppointmentServiceResolver::uuidValidationRules(),
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'address_2' => ['nullable', 'string', 'max:255'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
            'country_code' => ['nullable', 'string', 'size:2', 'alpha', 'uppercase'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'scheduled_at' => ['required', 'date'],
            'sms_consent' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
