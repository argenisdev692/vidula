<?php

declare(strict_types=1);

namespace Modules\Appointment\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Availability\Infrastructure\Persistence\Eloquent\Models\AvailabilityRuleEloquentModel;
use Modules\Services\Infrastructure\Persistence\Eloquent\Models\ServiceEloquentModel;
use Spatie\Honeypot\EncryptedTime;
use Tests\TestCase;

/**
 * CRM-token-gated REST API for the Astro marketing landing page:
 * POST /api/appointments/public + GET /api/appointments/public/honeypot.
 * Stateless JSON; Astro must send Authorization: Bearer {CRM_API_TOKEN}.
 */
final class PublicAppointmentApiTest extends TestCase
{
    use RefreshDatabase;

    private ServiceEloquentModel $websiteService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->websiteService = ServiceEloquentModel::factory()->create([
            'slug' => 'new_website',
            'name' => 'Business Website',
            'is_active' => true,
        ]);
    }

    /**
     * 2026-12-11 is a Friday with a seeded 09:00-13:00 availability window.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'client_type' => 'individual',
            'company_name' => null,
            'service_uuid' => $this->websiteService->uuid,
            'email' => 'ADA@Example.com',
            'phone' => '+15551234567',
            'address' => '123 Main St',
            'address_2' => null,
            'zip_code' => '10001',
            'city' => 'New York',
            'state' => 'NY',
            'country' => 'United States',
            'country_code' => 'US',
            'latitude' => null,
            'longitude' => null,
            'scheduled_at' => '2026-12-11 10:00:00',
            'sms_consent' => true,
            'notes' => 'Looking forward to it.',
            ...$overrides,
        ];
    }

    private function seedOpenFriday(): void
    {
        AvailabilityRuleEloquentModel::factory()->forDay(5)->slot('09:00', '13:00')->create();
    }

    public function test_without_crm_token_returns_401(): void
    {
        $this->seedOpenFriday();

        $this->postJson('/api/appointments/public', $this->payload())
            ->assertUnauthorized();

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_the_honeypot_descriptor_requires_crm_token(): void
    {
        $this->withHeaders($this->crmHeaders())
            ->getJson('/api/appointments/public/honeypot')
            ->assertOk()
            ->assertJsonStructure(['nameFieldName', 'validFromFieldName', 'encryptedValidFrom']);
    }

    public function test_a_valid_booking_is_stored_and_returns_201(): void
    {
        $this->seedOpenFriday();

        $this->withHeaders($this->crmHeaders())
            ->postJson('/api/appointments/public', $this->payload())
            ->assertCreated()
            ->assertJsonStructure(['message']);

        $this->assertDatabaseHas('appointments', [
            'email' => 'ada@example.com',
            'service_id' => $this->websiteService->id,
            'status_lead' => 'New',
            'is_spam' => false,
        ]);
    }

    public function test_a_spammy_booking_is_flagged_but_still_stored(): void
    {
        $this->seedOpenFriday();

        $this->withHeaders($this->crmHeaders())
            ->postJson('/api/appointments/public', $this->payload([
                'email' => 'spammer@example.com',
                'notes' => 'Best crypto casino offer — click here to earn money now!',
            ]))->assertCreated();

        // Flagged, never rejected: the lead is stored so a false positive can be reviewed.
        $this->assertDatabaseHas('appointments', [
            'email' => 'spammer@example.com',
            'is_spam' => true,
        ]);
    }

    public function test_validation_errors_return_422(): void
    {
        $this->withHeaders($this->crmHeaders())
            ->postJson('/api/appointments/public', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'last_name', 'client_type', 'email', 'scheduled_at']);
    }

    public function test_a_past_date_is_rejected_with_422(): void
    {
        $this->withHeaders($this->crmHeaders())
            ->postJson('/api/appointments/public', $this->payload(['scheduled_at' => '2020-01-01 10:00:00']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['scheduled_at']);
    }

    public function test_a_time_outside_availability_is_rejected_with_422(): void
    {
        $this->seedOpenFriday();

        $this->withHeaders($this->crmHeaders())
            ->postJson('/api/appointments/public', $this->payload(['scheduled_at' => '2026-12-11 20:00:00']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['scheduled_at']);
    }

    public function test_a_filled_honeypot_is_dropped_silently_with_the_same_201(): void
    {
        $this->seedOpenFriday();

        $this->withHeaders($this->crmHeaders())
            ->postJson('/api/appointments/public', $this->payload([
                'my_name' => 'http://spam.example',
                'valid_from' => EncryptedTime::create(now()->subMinute()),
            ]))->assertCreated();

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_a_resubmission_from_the_same_email_is_rejected(): void
    {
        $this->seedOpenFriday();
        AvailabilityRuleEloquentModel::factory()->forDay(5)->slot('14:00', '18:00')->create();

        $this->withHeaders($this->crmHeaders())
            ->postJson('/api/appointments/public', $this->payload())
            ->assertCreated();
        $this->assertDatabaseCount('appointments', 1);

        // A second request from the same email must NOT create/overwrite a lead —
        // a super-admin reschedules the existing one instead.
        $this->withHeaders($this->crmHeaders())
            ->postJson('/api/appointments/public', $this->payload(['scheduled_at' => '2026-12-11 15:00:00']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('appointments', 1);
        $this->assertDatabaseHas('appointments', [
            'email' => 'ada@example.com',
            'scheduled_at' => '2026-12-11 10:00:00',
        ]);
    }

    public function test_a_resubmission_with_a_different_email_case_is_still_rejected(): void
    {
        $this->seedOpenFriday();

        $this->withHeaders($this->crmHeaders())
            ->postJson('/api/appointments/public', $this->payload())
            ->assertCreated();

        // Same address, different casing — normalized at the DTO boundary, so the
        // duplicate guard still catches it (no case-variant second row).
        $this->withHeaders($this->crmHeaders())
            ->postJson('/api/appointments/public', $this->payload(['email' => 'ada@EXAMPLE.com']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_an_inactive_service_uuid_is_rejected_with_422(): void
    {
        $this->seedOpenFriday();
        $inactive = ServiceEloquentModel::factory()->create(['is_active' => false]);

        $this->withHeaders($this->crmHeaders())
            ->postJson('/api/appointments/public', $this->payload(['service_uuid' => $inactive->uuid]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['service_uuid']);
    }
}
