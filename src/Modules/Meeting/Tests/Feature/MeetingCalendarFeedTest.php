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
}
