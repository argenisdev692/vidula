<?php

declare(strict_types=1);

namespace Modules\Appointment\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Appointment\Infrastructure\Mail\AppointmentCancelledMail;
use Modules\Appointment\Infrastructure\Mail\AppointmentConfirmedMail;
use Modules\Appointment\Infrastructure\Mail\AppointmentReceivedMail;
use Modules\Appointment\Infrastructure\Mail\AppointmentRescheduledMail;
use Modules\Appointment\Infrastructure\Mail\NewLeadMail;
use Modules\Appointment\Infrastructure\Persistence\Eloquent\Models\AppointmentEloquentModel;
use Modules\Availability\Infrastructure\Persistence\Eloquent\Models\AvailabilityRuleEloquentModel;
use Modules\Meeting\Infrastructure\Persistence\Eloquent\Models\MeetingEloquentModel;
use Tests\TestCase;

final class AppointmentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function superAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');

        return $admin;
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'client_type' => 'individual',
            'company_name' => null,
            'project_type' => 'new_website',
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
            'sms_consent' => true,
            'notes' => null,
            'owner' => null,
        ];
    }

    public function test_super_admin_creates_a_lead_normalizing_the_email(): void
    {
        $this->actingAs($this->superAdmin())
            ->post('/appointments', $this->validPayload())
            ->assertRedirect();

        $this->assertDatabaseHas('appointments', [
            'first_name' => 'Ada',
            'email' => 'ada@example.com',
            'status_lead' => 'New',
        ]);
    }

    public function test_required_fields_are_validated(): void
    {
        $this->actingAs($this->superAdmin())
            ->post('/appointments', [])
            ->assertSessionHasErrors(['first_name', 'last_name', 'client_type', 'email']);
    }

    public function test_update_edits_the_captured_data(): void
    {
        $appointment = AppointmentEloquentModel::factory()->create();

        $this->actingAs($this->superAdmin())
            ->put("/appointments/{$appointment->uuid}", [...$this->validPayload(), 'email' => $appointment->email, 'notes' => 'Updated'])
            ->assertRedirect();

        $this->assertDatabaseHas('appointments', ['uuid' => $appointment->uuid, 'notes' => 'Updated']);
    }

    public function test_mark_as_read_flips_the_readed_flag(): void
    {
        $appointment = AppointmentEloquentModel::factory()->create(['readed' => false]);

        $this->actingAs($this->superAdmin())
            ->patch("/appointments/{$appointment->uuid}/read")
            ->assertRedirect();

        $this->assertDatabaseHas('appointments', ['uuid' => $appointment->uuid, 'readed' => true]);
    }

    public function test_confirming_requires_a_valid_slot_and_sends_the_confirmation_email(): void
    {
        Mail::fake();
        AvailabilityRuleEloquentModel::factory()->forDay(5)->slot('09:00', '13:00')->create();
        $appointment = AppointmentEloquentModel::factory()->create(['scheduled_at' => '2026-12-11 10:00:00']);

        $this->actingAs($this->superAdmin())
            ->patch("/appointments/{$appointment->uuid}/confirm", [])
            ->assertRedirect();

        $this->assertDatabaseHas('appointments', ['uuid' => $appointment->uuid, 'meeting_status' => 'Confirmed']);
        Mail::assertSent(
            AppointmentConfirmedMail::class,
            fn (AppointmentConfirmedMail $mail): bool => $mail->hasTo($appointment->email)
                && $mail->hasBcc((string) config('mail.from.address')),
        );
    }

    public function test_confirming_a_past_date_fails_validation(): void
    {
        $appointment = AppointmentEloquentModel::factory()->create(['scheduled_at' => '2020-01-01 10:00:00']);

        $this->actingAs($this->superAdmin())
            ->patch("/appointments/{$appointment->uuid}/confirm", [])
            ->assertSessionHasErrors(['scheduled_at']);
    }

    public function test_rescheduling_moves_the_previous_timestamp_and_sends_an_email(): void
    {
        Mail::fake();
        AvailabilityRuleEloquentModel::factory()->forDay(5)->slot('09:00', '18:00')->create();
        $appointment = AppointmentEloquentModel::factory()->confirmed()->create(['scheduled_at' => '2026-12-11 10:00:00']);

        $this->actingAs($this->superAdmin())
            ->patch("/appointments/{$appointment->uuid}/reschedule", ['scheduled_at' => '2026-12-11 15:00:00'])
            ->assertRedirect();

        $this->assertDatabaseHas('appointments', [
            'uuid' => $appointment->uuid,
            'meeting_status' => 'Rescheduled',
            'previous_scheduled_at' => '2026-12-11 10:00:00',
            'scheduled_at' => '2026-12-11 15:00:00',
        ]);
        Mail::assertSent(
            AppointmentRescheduledMail::class,
            fn (AppointmentRescheduledMail $mail): bool => $mail->hasTo($appointment->email)
                && $mail->hasBcc((string) config('mail.from.address')),
        );
    }

    public function test_cancelling_appends_the_reason_to_notes_and_sends_an_email(): void
    {
        Mail::fake();
        $appointment = AppointmentEloquentModel::factory()->confirmed()->create();

        $this->actingAs($this->superAdmin())
            ->patch("/appointments/{$appointment->uuid}/cancel", ['reason' => 'Client requested'])
            ->assertRedirect();

        $appointment->refresh();
        $this->assertSame('Cancelled', $appointment->meeting_status->value);
        $this->assertStringContainsString('Client requested', (string) $appointment->notes);
        Mail::assertSent(
            AppointmentCancelledMail::class,
            fn (AppointmentCancelledMail $mail): bool => $mail->hasTo($appointment->email)
                && $mail->hasBcc((string) config('mail.from.address')),
        );
    }

    public function test_adding_a_follow_up_call_advances_a_new_lead_to_called(): void
    {
        $appointment = AppointmentEloquentModel::factory()->create();

        $this->actingAs($this->superAdmin())
            ->post("/appointments/{$appointment->uuid}/follow-up-calls", ['note' => 'Left a voicemail'])
            ->assertRedirect();

        $appointment->refresh();
        $this->assertSame('Called', $appointment->status_lead->value);
        $this->assertCount(1, $appointment->follow_up_calls);
        $this->assertSame('Left a voicemail', $appointment->follow_up_calls[0]['note']);
    }

    public function test_booking_a_new_lead_notifies_both_the_company_inbox_and_the_client(): void
    {
        Mail::fake();
        AvailabilityRuleEloquentModel::factory()->forDay(5)->slot('09:00', '13:00')->create();

        $this->postJson('/api/appointments', [
            'first_name' => 'Grace',
            'last_name' => 'Hopper',
            'client_type' => 'individual',
            'email' => 'grace@navy.mil',
            'scheduled_at' => '2026-12-11 10:00:00',
        ])->assertCreated();

        Mail::assertSent(NewLeadMail::class);
        Mail::assertSent(AppointmentReceivedMail::class, fn (AppointmentReceivedMail $mail): bool => $mail->hasTo('grace@navy.mil'));
    }

    public function test_delete_then_restore_a_lead(): void
    {
        $admin = $this->superAdmin();
        $appointment = AppointmentEloquentModel::factory()->create();

        $this->actingAs($admin)->delete("/appointments/{$appointment->uuid}")->assertRedirect();
        $this->assertSoftDeleted('appointments', ['uuid' => $appointment->uuid]);

        $this->actingAs($admin)->post("/appointments/{$appointment->uuid}/restore")->assertRedirect();
        $this->assertDatabaseHas('appointments', ['uuid' => $appointment->uuid, 'deleted_at' => null]);
    }

    public function test_bulk_delete_then_restore(): void
    {
        $admin = $this->superAdmin();
        $uuids = AppointmentEloquentModel::factory()->count(3)->create()->pluck('uuid')->all();

        $this->actingAs($admin)->post('/appointments/bulk-delete', ['uuids' => $uuids])->assertRedirect();
        foreach ($uuids as $uuid) {
            $this->assertSoftDeleted('appointments', ['uuid' => $uuid]);
        }

        $this->actingAs($admin)->post('/appointments/bulk-restore', ['uuids' => $uuids])->assertRedirect();
        foreach ($uuids as $uuid) {
            $this->assertDatabaseHas('appointments', ['uuid' => $uuid, 'deleted_at' => null]);
        }
    }

    public function test_bulk_delete_rejects_more_than_500_uuids(): void
    {
        $uuids = array_map(static fn (): string => (string) Str::uuid7(), range(1, 501));

        $this->actingAs($this->superAdmin())
            ->postJson('/appointments/bulk-delete', ['uuids' => $uuids])
            ->assertStatus(422)
            ->assertJsonValidationErrors('uuids');
    }

    public function test_search_filter_narrows_the_list(): void
    {
        AppointmentEloquentModel::factory()->create(['email' => 'grace@navy.mil']);
        AppointmentEloquentModel::factory()->create(['email' => 'someone@else.com']);

        $this->actingAs($this->superAdmin())
            ->getJson('/appointments?search=grace@navy.mil')
            ->assertOk()
            ->assertJsonFragment(['email' => 'grace@navy.mil'])
            ->assertJsonMissing(['email' => 'someone@else.com']);
    }

    public function test_project_type_filter_narrows_the_list(): void
    {
        AppointmentEloquentModel::factory()->create(['email' => 'ecommerce@lead.com', 'project_type' => 'ecommerce']);
        AppointmentEloquentModel::factory()->create(['email' => 'website@lead.com', 'project_type' => 'new_website']);

        $this->actingAs($this->superAdmin())
            ->getJson('/appointments?project_type=ecommerce')
            ->assertOk()
            ->assertJsonFragment(['email' => 'ecommerce@lead.com'])
            ->assertJsonMissing(['email' => 'website@lead.com']);
    }

    public function test_scheduled_date_range_filter_narrows_the_list(): void
    {
        AppointmentEloquentModel::factory()->create([
            'email' => 'in-range@lead.com',
            'scheduled_at' => '2026-12-11 10:00:00',
        ]);
        AppointmentEloquentModel::factory()->create([
            'email' => 'out-of-range@lead.com',
            'scheduled_at' => '2027-01-15 10:00:00',
        ]);

        $this->actingAs($this->superAdmin())
            ->getJson('/appointments?scheduled_from=2026-12-01&scheduled_to=2026-12-31')
            ->assertOk()
            ->assertJsonFragment(['email' => 'in-range@lead.com'])
            ->assertJsonMissing(['email' => 'out-of-range@lead.com']);
    }

    public function test_exports_the_pipeline_as_excel(): void
    {
        AppointmentEloquentModel::factory()->count(2)->create();

        $this->actingAs($this->superAdmin())
            ->get('/appointments/export?format=xlsx')
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_exports_the_pipeline_as_csv(): void
    {
        AppointmentEloquentModel::factory()->count(2)->create();

        $this->actingAs($this->superAdmin())
            ->get('/appointments/export?format=csv')
            ->assertOk();
    }

    public function test_exports_the_pipeline_as_pdf(): void
    {
        AppointmentEloquentModel::factory()->count(2)->create();

        $this->actingAs($this->superAdmin())
            ->get('/appointments/export?format=pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_lead_and_meeting_share_polymorphic_attendee_relations(): void
    {
        $admin = $this->superAdmin();
        $lead = AppointmentEloquentModel::factory()->create();

        $this->actingAs($admin)
            ->post('/meetings', [
                'title' => 'Lead discovery call',
                'description' => null,
                'starts_at' => '2026-12-11 10:00:00',
                'attendees' => [
                    ['type' => 'lead', 'uuid' => $lead->uuid],
                ],
            ])
            ->assertRedirect();

        $attendances = $lead->fresh()->meetingAttendances()->with('meeting:id,uuid,title')->get();

        $this->assertCount(1, $attendances);
        $this->assertSame('lead', $attendances->first()?->attendable_type);
        $this->assertSame('Lead discovery call', $attendances->first()?->meeting?->title);

        $meeting = MeetingEloquentModel::query()->where('title', 'Lead discovery call')->firstOrFail();
        $this->assertTrue(
            $meeting->appointments->contains(static fn (AppointmentEloquentModel $row): bool => $row->uuid === $lead->uuid),
        );
    }

    public function test_a_user_without_permission_cannot_manage_appointments(): void
    {
        $plain = User::factory()->create();
        $plain->assignRole('USER');

        $this->actingAs($plain)->get('/appointments')->assertForbidden();
        $this->actingAs($plain)->post('/appointments', $this->validPayload())->assertForbidden();
    }
}
