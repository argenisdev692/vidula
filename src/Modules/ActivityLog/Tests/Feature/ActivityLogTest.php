<?php

declare(strict_types=1);

namespace Modules\ActivityLog\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

final class ActivityLogTest extends TestCase
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

    private function makeActivity(string $description, ?\DateTimeInterface $createdAt = null): Activity
    {
        /** @var Activity $activity */
        $activity = activity()->event('created')->log($description);

        if ($createdAt !== null) {
            $activity->forceFill(['created_at' => $createdAt])->save();
        }

        return $activity;
    }

    public function test_super_admin_lists_activity_logs(): void
    {
        $activity = $this->makeActivity('did a thing');

        $this->actingAs($this->superAdmin())
            ->getJson('/activity-logs')
            ->assertOk()
            ->assertJsonPath('data.0.id', $activity->id)
            ->assertJsonPath('data.0.event', 'created');
    }

    public function test_super_admin_shows_a_single_entry(): void
    {
        $activity = $this->makeActivity('did another thing');

        $this->actingAs($this->superAdmin())
            ->getJson("/activity-logs/{$activity->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $activity->id)
            ->assertJsonPath('data.description', 'did another thing');
    }

    public function test_users_without_permission_cannot_view_the_trail(): void
    {
        $this->makeActivity('sensitive');

        $this->actingAs(User::factory()->create())
            ->getJson('/activity-logs')
            ->assertForbidden();
    }

    public function test_super_admin_can_export_the_trail_as_csv(): void
    {
        $this->makeActivity('exported thing');

        $response = $this->actingAs($this->superAdmin())
            ->get('/activity-logs/export?format=csv');

        $response->assertOk();
        $this->assertStringContainsString('activity-logs.csv', (string) $response->headers->get('content-disposition'));
    }

    public function test_export_rejects_an_unsupported_format(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/activity-logs/export?format=exe')
            ->assertStatus(422);
    }

    public function test_archive_command_purges_rows_past_the_window_and_keeps_recent(): void
    {
        Storage::fake(config('filesystems.cloud'));

        $old = $this->makeActivity('ancient', now()->subDays(120));
        $recent = $this->makeActivity('fresh');

        $this->artisan('activity-log:archive', ['--days' => 90])->assertSuccessful();

        $this->assertDatabaseMissing('activity_log', ['id' => $old->id]);
        $this->assertDatabaseHas('activity_log', ['id' => $recent->id]);
        $this->assertDatabaseHas('activity_log', ['event' => 'activity_log.archived']);
        $this->assertNotEmpty(Storage::disk(config('filesystems.cloud'))->allFiles('archives/activity-log'));
    }

    public function test_archive_dry_run_deletes_nothing(): void
    {
        Storage::fake(config('filesystems.cloud'));

        $old = $this->makeActivity('ancient', now()->subDays(120));

        $this->artisan('activity-log:archive', ['--days' => 90, '--dry-run' => true])->assertSuccessful();

        $this->assertDatabaseHas('activity_log', ['id' => $old->id]);
        $this->assertEmpty(Storage::disk(config('filesystems.cloud'))->allFiles('archives/activity-log'));
    }
}
