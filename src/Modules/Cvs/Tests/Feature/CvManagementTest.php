<?php

declare(strict_types=1);

namespace Modules\Cvs\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Cvs\Infrastructure\Persistence\Eloquent\Models\CvEloquentModel;
use Tests\TestCase;

final class CvManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('r2');
    }

    private function superAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');

        return $admin;
    }

    public function test_super_admin_uploads_a_markdown_cv(): void
    {
        $admin = $this->superAdmin();
        $file = UploadedFile::fake()->createWithContent('resume.md', "# Fullstack Resume\n\nLaravel + Vue");

        $this->actingAs($admin)
            ->post('/cvs', [
                'title' => 'My Fullstack CV',
                'niche' => 'fullstack',
                'is_primary' => true,
                'file' => $file,
            ])
            ->assertRedirect();

        $cv = CvEloquentModel::query()->where('title', 'My Fullstack CV')->firstOrFail();

        $this->assertSame($admin->id, $cv->user_id);
        $this->assertSame('fullstack', $cv->niche);
        $this->assertTrue($cv->is_primary);
        $this->assertSame('md', $cv->file_type);
        $this->assertStringContainsString('Fullstack Resume', (string) $cv->raw_text);
    }

    public function test_create_without_file_is_rejected(): void
    {
        $this->actingAs($this->superAdmin())
            ->post('/cvs', [
                'title' => 'Missing file',
                'niche' => 'fullstack',
                'is_primary' => false,
            ])
            ->assertSessionHasErrors('file');
    }

    public function test_invalid_niche_is_rejected(): void
    {
        $file = UploadedFile::fake()->createWithContent('resume.md', '# CV');

        $this->actingAs($this->superAdmin())
            ->post('/cvs', [
                'title' => 'Bad niche',
                'niche' => 'marketing',
                'is_primary' => false,
                'file' => $file,
            ])
            ->assertSessionHasErrors('niche');
    }

    public function test_setting_primary_clears_previous_primary(): void
    {
        $admin = $this->superAdmin();
        $existing = CvEloquentModel::factory()->primary()->create(['user_id' => $admin->id]);
        $file = UploadedFile::fake()->createWithContent('new.md', '# New primary');

        $this->actingAs($admin)
            ->post('/cvs', [
                'title' => 'New primary CV',
                'niche' => 'fullstack',
                'is_primary' => true,
                'file' => $file,
            ])
            ->assertRedirect();

        $existing->refresh();
        $this->assertFalse($existing->is_primary);
        $this->assertTrue(
            CvEloquentModel::query()->where('title', 'New primary CV')->value('is_primary'),
        );
    }

    public function test_delete_then_restore_a_cv(): void
    {
        $admin = $this->superAdmin();
        $cv = CvEloquentModel::factory()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)->delete("/cvs/{$cv->uuid}")->assertRedirect();
        $this->assertSoftDeleted('cvs', ['uuid' => $cv->uuid]);

        $this->actingAs($admin)->post("/cvs/{$cv->uuid}/restore")->assertRedirect();
        $this->assertDatabaseHas('cvs', ['uuid' => $cv->uuid, 'deleted_at' => null]);
    }

    public function test_bulk_delete_then_restore(): void
    {
        $admin = $this->superAdmin();
        $uuids = CvEloquentModel::factory()->count(3)->create(['user_id' => $admin->id])->pluck('uuid')->all();

        $this->actingAs($admin)->post('/cvs/bulk-delete', ['uuids' => $uuids])->assertRedirect();
        foreach ($uuids as $uuid) {
            $this->assertSoftDeleted('cvs', ['uuid' => $uuid]);
        }

        $this->actingAs($admin)->post('/cvs/bulk-restore', ['uuids' => $uuids])->assertRedirect();
        foreach ($uuids as $uuid) {
            $this->assertDatabaseHas('cvs', ['uuid' => $uuid, 'deleted_at' => null]);
        }
    }

    public function test_guest_cannot_view_cvs_index(): void
    {
        $this->get('/cvs')->assertRedirect('/login');
    }

    public function test_admin_role_can_view_cvs_index(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('ADMIN');

        $this->actingAs($admin)->get('/cvs')->assertOk();
    }

    public function test_export_csv_is_streamed(): void
    {
        $admin = $this->superAdmin();
        CvEloquentModel::factory()->create(['user_id' => $admin->id, 'title' => 'Export Me']);

        $this->actingAs($admin)
            ->get('/cvs/export?format=csv')
            ->assertOk();
    }
}
