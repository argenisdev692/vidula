<?php

declare(strict_types=1);

namespace Modules\Meeting\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Appointment\Infrastructure\Persistence\Eloquent\Models\AppointmentEloquentModel;
use Modules\ContactSupport\Infrastructure\Persistence\Eloquent\Models\ContactSupportEloquentModel;
use Modules\Meeting\Infrastructure\Persistence\Eloquent\Models\MeetingEloquentModel;
use Tests\TestCase;

final class MeetingManagementTest extends TestCase
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
        $attendeeUser = User::factory()->create();
        $lead = AppointmentEloquentModel::factory()->create();

        return [
            'title' => 'Kickoff call',
            'description' => 'Discuss project scope.',
            'starts_at' => '2026-12-11 10:00:00',
            'attendees' => [
                ['type' => 'user', 'uuid' => $attendeeUser->uuid],
                ['type' => 'lead', 'uuid' => $lead->uuid],
            ],
        ];
    }

    public function test_super_admin_creates_a_meeting_with_mixed_attendees(): void
    {
        $this->actingAs($this->superAdmin())
            ->post('/meetings', $this->validPayload())
            ->assertRedirect();

        $this->assertDatabaseHas('meetings', ['title' => 'Kickoff call', 'status' => 'Scheduled']);
        $meeting = MeetingEloquentModel::query()->where('title', 'Kickoff call')->firstOrFail();
        $this->assertCount(2, $meeting->attendees);
        $this->assertSame('2026-12-11 10:30:00', $meeting->ends_at->format('Y-m-d H:i:s'));
    }

    public function test_organizer_is_set_from_the_authenticated_user_never_the_payload(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->post('/meetings', [...$this->validPayload(), 'organizer_id' => 999999])
            ->assertRedirect();

        $meeting = MeetingEloquentModel::query()->where('title', 'Kickoff call')->firstOrFail();
        $this->assertSame($admin->id, $meeting->organizer_id);
    }

    public function test_required_fields_are_validated(): void
    {
        $this->actingAs($this->superAdmin())
            ->post('/meetings', [])
            ->assertSessionHasErrors(['title', 'starts_at']);
    }

    public function test_ends_at_is_derived_from_config_duration_and_ignored_from_payload(): void
    {
        config(['meeting.duration_minutes' => 30]);

        $this->actingAs($this->superAdmin())
            ->post('/meetings', [...$this->validPayload(), 'ends_at' => '2026-12-11 18:00:00'])
            ->assertRedirect();

        $meeting = MeetingEloquentModel::query()->where('title', 'Kickoff call')->firstOrFail();
        $this->assertSame('2026-12-11 10:30:00', $meeting->ends_at->format('Y-m-d H:i:s'));
    }

    public function test_a_dangling_attendee_uuid_is_rejected(): void
    {
        $this->actingAs($this->superAdmin())
            ->postJson('/meetings', [
                ...$this->validPayload(),
                'attendees' => [['type' => 'user', 'uuid' => (string) Str::uuid7()]],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('attendees');
    }

    public function test_update_replaces_the_attendee_set(): void
    {
        $admin = $this->superAdmin();
        $this->actingAs($admin)->post('/meetings', $this->validPayload());
        $meeting = MeetingEloquentModel::query()->where('title', 'Kickoff call')->firstOrFail();

        $contact = ContactSupportEloquentModel::factory()->create();

        $this->actingAs($admin)
            ->put("/meetings/{$meeting->uuid}", [
                'title' => 'Kickoff call (updated)',
                'description' => null,
                'starts_at' => '2026-12-11 10:00:00',
                'attendees' => [['type' => 'contact', 'uuid' => $contact->uuid]],
            ])
            ->assertRedirect();

        $meeting->refresh();
        $this->assertSame('Kickoff call (updated)', $meeting->title);
        $this->assertCount(1, $meeting->attendees);
        $this->assertSame('contact', $meeting->attendees->first()->attendable_type);
        $this->assertSame('2026-12-11 10:30:00', $meeting->ends_at->format('Y-m-d H:i:s'));
    }

    public function test_cancel_flips_status_to_cancelled(): void
    {
        $meeting = MeetingEloquentModel::factory()->create();

        $this->actingAs($this->superAdmin())
            ->patch("/meetings/{$meeting->uuid}/cancel")
            ->assertRedirect();

        $this->assertDatabaseHas('meetings', ['uuid' => $meeting->uuid, 'status' => 'Cancelled']);
    }

    public function test_delete_then_restore_a_meeting(): void
    {
        $admin = $this->superAdmin();
        $meeting = MeetingEloquentModel::factory()->create();

        $this->actingAs($admin)->delete("/meetings/{$meeting->uuid}")->assertRedirect();
        $this->assertSoftDeleted('meetings', ['uuid' => $meeting->uuid]);

        $this->actingAs($admin)->post("/meetings/{$meeting->uuid}/restore")->assertRedirect();
        $this->assertDatabaseHas('meetings', ['uuid' => $meeting->uuid, 'deleted_at' => null]);
    }

    public function test_bulk_delete_then_restore(): void
    {
        $admin = $this->superAdmin();
        $uuids = MeetingEloquentModel::factory()->count(3)->create()->pluck('uuid')->all();

        $this->actingAs($admin)->post('/meetings/bulk-delete', ['uuids' => $uuids])->assertRedirect();
        foreach ($uuids as $uuid) {
            $this->assertSoftDeleted('meetings', ['uuid' => $uuid]);
        }

        $this->actingAs($admin)->post('/meetings/bulk-restore', ['uuids' => $uuids])->assertRedirect();
        foreach ($uuids as $uuid) {
            $this->assertDatabaseHas('meetings', ['uuid' => $uuid, 'deleted_at' => null]);
        }
    }

    public function test_bulk_delete_rejects_more_than_500_uuids(): void
    {
        $uuids = array_map(static fn (): string => (string) Str::uuid7(), range(1, 501));

        $this->actingAs($this->superAdmin())
            ->postJson('/meetings/bulk-delete', ['uuids' => $uuids])
            ->assertStatus(422)
            ->assertJsonValidationErrors('uuids');
    }

    /**
     * OWASP API1/BOLA regression guard: a staff member who holds
     * UPDATE_MEETINGS but is NOT the organizer, and does not hold
     * VIEW_ANY_MEETINGS, must not be able to edit/cancel/delete someone
     * else's meeting via the permission gate alone.
     */
    public function test_a_non_organizer_without_elevated_permission_cannot_modify_the_meeting(): void
    {
        $meeting = MeetingEloquentModel::factory()->create();

        $moderator = User::factory()->create();
        $moderator->assignRole('MODERATOR');
        $moderator->givePermissionTo('UPDATE_MEETINGS', 'DELETE_MEETINGS');

        $this->actingAs($moderator)->patch("/meetings/{$meeting->uuid}/cancel")->assertForbidden();
        $this->actingAs($moderator)->delete("/meetings/{$meeting->uuid}")->assertForbidden();
    }

    /**
     * OWASP API1/BOLA regression guard: `VIEW_MEETINGS` alone is a base
     * (non-elevated) permission — it must not let a non-organizer read
     * someone else's meeting via `GET /meetings/{uuid}`.
     */
    public function test_a_non_organizer_without_elevated_permission_cannot_view_the_meeting(): void
    {
        $meeting = MeetingEloquentModel::factory()->create();

        $moderator = User::factory()->create();
        $moderator->assignRole('MODERATOR');
        $moderator->givePermissionTo('VIEW_MEETINGS');

        $this->actingAs($moderator)->get("/meetings/{$meeting->uuid}")->assertForbidden();
    }

    /**
     * Same BOLA guard for restore: `RESTORE_MEETINGS` alone must not let a
     * non-organizer restore someone else's soft-deleted meeting.
     */
    public function test_a_non_organizer_without_elevated_permission_cannot_restore_the_meeting(): void
    {
        $meeting = MeetingEloquentModel::factory()->create();
        $meeting->delete();

        $moderator = User::factory()->create();
        $moderator->assignRole('MODERATOR');
        $moderator->givePermissionTo('RESTORE_MEETINGS');

        $this->actingAs($moderator)->post("/meetings/{$meeting->uuid}/restore")->assertForbidden();
    }

    public function test_a_user_without_permission_cannot_manage_meetings(): void
    {
        $plain = User::factory()->create();
        $plain->assignRole('USER');

        $this->actingAs($plain)->get('/meetings')->assertForbidden();
        $this->actingAs($plain)->post('/meetings', $this->validPayload())->assertForbidden();
    }

    public function test_search_filter_narrows_the_list(): void
    {
        MeetingEloquentModel::factory()->create(['title' => 'Budget review']);
        MeetingEloquentModel::factory()->create(['title' => 'Design sync']);

        $this->actingAs($this->superAdmin())
            ->getJson('/meetings?search=Budget')
            ->assertOk()
            ->assertJsonFragment(['title' => 'Budget review'])
            ->assertJsonMissing(['title' => 'Design sync']);
    }

    public function test_exports_the_meeting_list_as_excel(): void
    {
        MeetingEloquentModel::factory()->count(2)->create();

        $this->actingAs($this->superAdmin())
            ->get('/meetings/export?format=xlsx')
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
