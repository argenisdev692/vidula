<?php

declare(strict_types=1);

namespace Modules\Meeting\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Appointment\Domain\ValueObjects\MeetingStatus as AppointmentMeetingStatus;
use Modules\Appointment\Infrastructure\Persistence\Eloquent\Models\AppointmentEloquentModel;
use Modules\ContactSupport\Infrastructure\Persistence\Eloquent\Models\ContactSupportEloquentModel;
use Modules\Meeting\Infrastructure\Persistence\Eloquent\Models\MeetingEloquentModel;
use Tests\TestCase;

final class MeetingCalendarFeedTest extends TestCase
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

    public function test_the_feed_combines_own_meetings_with_the_appointment_overlay(): void
    {
        MeetingEloquentModel::factory()->create([
            'title' => 'Internal sync',
            'starts_at' => '2026-12-11 09:00:00',
            'ends_at' => '2026-12-11 09:30:00',
        ]);
        AppointmentEloquentModel::factory()->create([
            'first_name' => 'Grace',
            'last_name' => 'Hopper',
            'meeting_status' => AppointmentMeetingStatus::Confirmed,
            'scheduled_at' => '2026-12-11 14:00:00',
        ]);

        $response = $this->actingAs($this->superAdmin())
            ->getJson('/meetings/calendar-feed?start=2026-12-01&end=2026-12-31')
            ->assertOk();

        $sources = collect($response->json('data'))->pluck('source')->all();
        $this->assertContains('meeting', $sources);
        $this->assertContains('appointment', $sources);
    }

    public function test_the_range_is_capped_at_92_days(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->getJson('/meetings/calendar-feed?start=2026-01-01&end=2027-12-31')
            ->assertOk();

        $this->assertIsArray($response->json('data'));
    }

    public function test_attendee_search_returns_only_minimal_fields(): void
    {
        $user = User::factory()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);
        ContactSupportEloquentModel::factory()->create(['first_name' => 'Ada', 'last_name' => 'Contact']);

        $response = $this->actingAs($this->superAdmin())
            ->getJson('/meetings/attendees/search?q=Ada')
            ->assertOk();

        $first = $response->json('data.0');
        $this->assertEqualsCanonicalizing(['type', 'uuid', 'label'], array_keys($first));
        $this->assertNotSame((string) $user->id, $first['uuid'] ?? null);
    }

    public function test_attendee_search_can_be_scoped_to_a_single_type(): void
    {
        User::factory()->create(['first_name' => 'Grace', 'last_name' => 'Hopper']);
        AppointmentEloquentModel::factory()->create(['first_name' => 'Grace', 'last_name' => 'Lead']);

        $response = $this->actingAs($this->superAdmin())
            ->getJson('/meetings/attendees/search?q=Grace&type=lead')
            ->assertOk();

        $types = collect($response->json('data'))->pluck('type')->unique()->all();
        $this->assertSame(['lead'], $types);
    }

    public function test_attendee_search_matches_email_across_sources(): void
    {
        User::factory()->create([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada.unique@example.com',
        ]);
        AppointmentEloquentModel::factory()->create([
            'first_name' => 'Grace',
            'last_name' => 'Hopper',
            'email' => 'grace.lead@example.com',
        ]);
        ContactSupportEloquentModel::factory()->create([
            'first_name' => 'Alan',
            'last_name' => 'Turing',
            'email' => 'alan.contact@example.com',
        ]);

        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->getJson('/meetings/attendees/search?q=ada.unique@example.com')
            ->assertOk()
            ->assertJsonFragment(['type' => 'user']);

        $this->actingAs($admin)
            ->getJson('/meetings/attendees/search?q=grace.lead@example.com')
            ->assertOk()
            ->assertJsonFragment(['type' => 'lead']);

        $this->actingAs($admin)
            ->getJson('/meetings/attendees/search?q=alan.contact@example.com')
            ->assertOk()
            ->assertJsonFragment(['type' => 'contact']);
    }

    public function test_quick_lead_creates_an_appointment_and_returns_attendee_option(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->postJson('/meetings/attendees/quick-lead', [
                'first_name' => 'New',
                'last_name' => 'Lead',
                'email' => 'new.lead@example.com',
                'phone' => '+14155552671',
            ])
            ->assertCreated()
            ->assertJsonPath('data.type', 'lead')
            ->assertJsonPath('data.label', 'New Lead · new.lead@example.com');

        $this->assertDatabaseHas('appointments', [
            'email' => 'new.lead@example.com',
            'first_name' => 'New',
            'last_name' => 'Lead',
            'phone' => '+14155552671',
        ]);
        $this->assertNotEmpty($response->json('data.uuid'));
    }

    public function test_meeting_availability_is_reachable_for_meeting_creators(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->getJson('/meetings/availability?from=2026-12-01&to=2026-12-07')
            ->assertOk();

        $this->assertIsArray($response->json('data'));
        $this->assertSame(30, $response->json('meta.duration_minutes'));
    }

    public function test_a_user_without_meeting_permission_cannot_access_availability(): void
    {
        $plain = User::factory()->create();
        $plain->assignRole('USER');

        $this->actingAs($plain)
            ->getJson('/meetings/availability?from=2026-12-01&to=2026-12-07')
            ->assertForbidden();
    }
}
