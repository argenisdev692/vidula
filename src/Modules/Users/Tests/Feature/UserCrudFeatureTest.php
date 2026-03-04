<?php

declare(strict_types=1);

namespace Modules\Users\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserEloquentModel;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * UserCrudFeatureTest — Full HTTP CRUD + export feature tests.
 */
final class UserCrudFeatureTest extends TestCase
{
    use RefreshDatabase;

    private UserEloquentModel $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a Super Admin user
        $this->admin = UserEloquentModel::query()->create([
            'uuid' => fake()->uuid(),
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $role = Role::firstOrCreate(['name' => 'SUPER_ADMIN', 'guard_name' => 'web']);
        $this->admin->assignRole($role);
    }

    // ── INDEX ──

    public function test_admin_can_list_users(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/users/data/admin');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['total', 'perPage', 'currentPage', 'lastPage'],
            ]);
    }

    // ── STORE ──

    public function test_admin_can_create_user(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/users/data/admin', [
                'name' => 'New User',
                'email' => 'newuser@example.com',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'New User');
    }

    public function test_create_user_validation_requires_name_and_email(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/users/data/admin', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email']);
    }

    // ── SHOW ──

    public function test_admin_can_show_user(): void
    {
        $user = UserEloquentModel::query()->create([
            'uuid' => $uuid = fake()->uuid(),
            'name' => 'Show Me',
            'email' => 'showme@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/users/data/admin/{$uuid}");

        $response->assertStatus(200)
            ->assertJsonPath('data.uuid', $uuid);
    }

    // ── UPDATE ──

    public function test_admin_can_update_user(): void
    {
        $user = UserEloquentModel::query()->create([
            'uuid' => $uuid = fake()->uuid(),
            'name' => 'Original',
            'email' => 'original@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)
            ->putJson("/users/data/admin/{$uuid}", [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name');
    }

    // ── DELETE ──

    public function test_admin_can_soft_delete_user(): void
    {
        $user = UserEloquentModel::query()->create([
            'uuid' => $uuid = fake()->uuid(),
            'name' => 'DeleteMe',
            'email' => 'deleteme@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/users/data/admin/{$uuid}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('users', ['uuid' => $uuid]);
    }

    // ── RESTORE ──

    public function test_admin_can_restore_user(): void
    {
        $user = UserEloquentModel::query()->create([
            'uuid' => $uuid = fake()->uuid(),
            'name' => 'RestoreMe',
            'email' => 'restoreme@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $user->delete();

        $response = $this->actingAs($this->admin)
            ->patchJson("/users/data/admin/{$uuid}/restore");

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', ['uuid' => $uuid, 'deleted_at' => null]);
    }

    // ── BULK DELETE ──

    public function test_admin_can_bulk_delete_users(): void
    {
        $uuids = [];
        for ($i = 0; $i < 3; $i++) {
            $user = UserEloquentModel::query()->create([
                'uuid' => $uuid = fake()->uuid(),
                'name' => "Bulk {$i}",
                'email' => "bulk{$i}@example.com",
                'password' => bcrypt('password'),
                'status' => 'active',
            ]);
            $uuids[] = $uuid;
        }

        $response = $this->actingAs($this->admin)
            ->postJson('/users/data/admin/bulk-delete', [
                'uuids' => $uuids,
            ]);

        $response->assertStatus(204);

        foreach ($uuids as $uuid) {
            $this->assertSoftDeleted('users', ['uuid' => $uuid]);
        }
    }

    // ── EXPORT ──

    public function test_admin_can_export_users_excel(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/users/data/admin/export?format=excel');

        $response->assertStatus(200);
    }
}
