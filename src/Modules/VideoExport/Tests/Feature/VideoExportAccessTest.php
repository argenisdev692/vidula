<?php

declare(strict_types=1);

namespace Modules\VideoExport\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Modules\VideoExport\Infrastructure\Queue\ProcessVideoExportJob;
use Tests\TestCase;

final class VideoExportAccessTest extends TestCase
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

    public function test_guests_are_redirected_from_index(): void
    {
        $this->get('/video-export')->assertRedirect();
    }

    public function test_users_without_permission_cannot_view_panel(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/video-export')
            ->assertForbidden();
    }

    public function test_super_admin_can_view_panel(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/video-export')
            ->assertOk();
    }

    public function test_users_without_create_cannot_presign(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/video-export/uploads/presign', [
                'filename' => 'clip.mp4',
                'content_type' => 'video/mp4',
                'size_bytes' => 1024,
            ])
            ->assertForbidden();
    }

    public function test_enqueue_rejects_local_paths(): void
    {
        Queue::fake();

        $this->actingAs($this->superAdmin())
            ->postJson('/video-export', [
                'job_uuid' => (string) Str::uuid(),
                'mode' => 'merge',
                'video_paths' => ['/tmp/evil.mp4'],
            ])
            ->assertUnprocessable();

        Queue::assertNothingPushed();
    }

    public function test_status_is_not_found_for_unknown_job(): void
    {
        $uuid = (string) Str::uuid();

        $this->actingAs($this->superAdmin())
            ->getJson('/video-export/jobs/'.$uuid)
            ->assertOk()
            ->assertJsonPath('data.status', 'not_found');
    }

    public function test_enqueue_merge_queues_job_when_urls_are_https(): void
    {
        Queue::fake();
        config(['filesystems.disks.r2.url' => 'https://cdn.example.test']);

        $jobUuid = (string) Str::uuid();

        $this->actingAs($this->superAdmin())
            ->postJson('/video-export', [
                'job_uuid' => $jobUuid,
                'mode' => 'merge',
                'video_paths' => ['https://cdn.example.test/video-exports/_parts/a.mp4'],
            ])
            ->assertAccepted()
            ->assertJsonPath('data.status', 'queued')
            ->assertJsonPath('data.job_uuid', $jobUuid);

        Queue::assertPushed(ProcessVideoExportJob::class);
    }

    public function test_api_enqueue_requires_sanctum(): void
    {
        $this->postJson('/api/video-export', [
            'job_uuid' => (string) Str::uuid(),
            'mode' => 'merge',
            'video_paths' => ['https://cdn.example.test/a.mp4'],
        ])->assertUnauthorized();
    }

    public function test_api_super_admin_can_poll_not_found(): void
    {
        $uuid = (string) Str::uuid();

        $this->actingAs($this->superAdmin(), 'sanctum')
            ->getJson('/api/video-export/jobs/'.$uuid)
            ->assertOk()
            ->assertJsonPath('data.status', 'not_found');
    }
}
