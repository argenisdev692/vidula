<?php

declare(strict_types=1);

namespace Modules\Backup\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Console\QueuedCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class BackupManagementTest extends TestCase
{
    use RefreshDatabase;

    private const BACKUP_FILE = '2026-07-06-02-00-00.zip';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        config(['backup.backup.destination.disks' => ['local']]);
    }

    private function superAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');

        return $admin;
    }

    private function seedBackupFile(): string
    {
        Storage::fake('local');
        $path = config('backup.backup.name').'/'.self::BACKUP_FILE;
        Storage::disk('local')->put($path, 'fake-zip-contents');

        return $path;
    }

    public function test_super_admin_lists_backups_with_status(): void
    {
        $this->seedBackupFile();

        $this->actingAs($this->superAdmin())
            ->getJson('/backups')
            ->assertOk()
            ->assertJsonPath('backups.0.id', self::BACKUP_FILE)
            ->assertJsonPath('status.backup_count', 1);
    }

    public function test_users_without_permission_cannot_view_backups(): void
    {
        $this->seedBackupFile();

        $this->actingAs(User::factory()->create())
            ->getJson('/backups')
            ->assertForbidden();
    }

    public function test_super_admin_downloads_a_backup(): void
    {
        $this->seedBackupFile();

        $this->actingAs($this->superAdmin())
            ->get('/backups/'.self::BACKUP_FILE.'/download')
            ->assertOk()
            ->assertDownload(self::BACKUP_FILE);
    }

    public function test_downloading_an_unknown_backup_returns_404(): void
    {
        $this->seedBackupFile();

        $this->actingAs($this->superAdmin())
            ->get('/backups/does-not-exist.zip/download')
            ->assertNotFound();
    }

    public function test_super_admin_queues_an_on_demand_backup(): void
    {
        Queue::fake();
        $this->seedBackupFile();

        $this->actingAs($this->superAdmin())
            ->from('/backups')
            ->post('/backups/run')
            ->assertRedirect('/backups')
            ->assertSessionHas('success');

        Queue::assertPushed(QueuedCommand::class);
    }

    public function test_super_admin_deletes_a_backup(): void
    {
        $path = $this->seedBackupFile();

        $this->actingAs($this->superAdmin())
            ->from('/backups')
            ->delete('/backups/'.self::BACKUP_FILE)
            ->assertRedirect('/backups')
            ->assertSessionHas('success');

        Storage::disk('local')->assertMissing($path);
    }
}
