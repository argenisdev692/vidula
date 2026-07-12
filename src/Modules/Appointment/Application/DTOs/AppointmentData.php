<?php

declare(strict_types=1);

namespace Modules\Appointment\Application\DTOs;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Admin create/update payload for a lead's profile. Store and Update share the
 * full field set, so a single fused DTO is used (DTO Fusion Rule, mirrors
 * ContactSupportData). Scheduling (`scheduled_at`), `status_lead` and
 * `meeting_status` are intentionally NOT part of this DTO — they change through
 * the dedicated Confirm / Reschedule / Cancel / AddFollowUpCall actions, never a
 * generic mass-assigned update.
 */
#[MapInputName(SnakeCaseMapper::class)]
final class AppointmentData extends Data
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $clientType,
        public ?string $companyName,
        public ?string $projectType,
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
        public bool $smsConsent = false,
        public ?string $notes = null,
        public ?string $owner = null,
    ) {}

    /**
     * Normalize the email BEFORE the pipeline's validation pipe so the
     * `unique` rule below and the DB partial unique index (both case-sensitive
     * on PostgreSQL) agree on the same value — otherwise a case variant slips
     * past validation and surfaces as a 500 on the index instead of a 422.
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
        $uuid = request()->route('uuid');

        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'client_type' => ['required', Rule::in(['company', 'individual'])],
            'company_name' => ['nullable', 'string', 'max:255', 'required_if:client_type,company'],
            'project_type' => ['nullable', Rule::in(['new_website', 'redesign', 'ecommerce', 'landing_page', 'maintenance', 'other'])],
            'email' => [
                'required', 'string', 'email', 'max:255',
                // Mirrors the DB partial unique index (email unique WHERE deleted_at
                // IS NULL) — a soft-deleted row's email must not block reuse.
                Rule::unique('appointments', 'email')->whereNull('deleted_at')->ignore($uuid, 'uuid'),
            ],
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
            'sms_consent' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'owner' => ['nullable', 'string', 'max:255'],
        ];
    }
}
