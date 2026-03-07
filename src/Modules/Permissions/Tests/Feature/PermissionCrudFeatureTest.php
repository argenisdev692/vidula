<?php

declare(strict_types=1);

namespace Modules\Permissions\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Permissions\Infrastructure\Persistence\Eloquent\Models\PermissionEloquentModel;
use Modules\Roles\Infrastructure\Persistence\Eloquent\Models\RoleEloquentModel;
use Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserEloquentModel;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

final class PermissionCrudFeatureTest extends TestCase
{
    use RefreshDatabase;

    private UserEloquentModel $admin;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['VIEW_PERMISSIONS', 'CREATE_PERMISSIONS', 'UPDATE_PERMISSIONS', 'DELETE_PERMISSIONS'] as $permission) {
            PermissionEloquentModel::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ], [
                'uuid' => Uuid::uuid4()->toString(),
            ]);
        }

        $superAdminRole = RoleEloquentModel::firstOrCreate([
            'name' => 'SUPER_ADMIN',
            'guard_name' => 'web',
        ], [
            'uuid' => Uuid::uuid4()->toString(),
        ]);
        $superAdminRole->givePermissionTo(PermissionEloquentModel::where('guard_name', 'web')->get());

        $this->admin = UserEloquentModel::query()->create([
            'uuid' => fake()->uuid(),
            'name' => 'Admin',
            'email' => 'admin-permissions@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $this->admin->assignRole($superAdminRole);
    }

    public function test_admin_can_list_permissions(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/permissions/data/admin');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['total', 'perPage', 'currentPage', 'lastPage'],
            ]);
    }

    public function test_admin_can_create_permission(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/permissions/data/admin', [
                'name' => 'EXPORT_REPORTS',
                'guard_name' => 'web',
                'roles' => ['SUPER_ADMIN'],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'EXPORT_REPORTS');
    }

    public function test_admin_can_show_permission(): void
    {
        $permission = PermissionEloquentModel::query()->create([
            'uuid' => $uuid = fake()->uuid(),
            'name' => 'VIEW_AUDIT_LOGS',
            'guard_name' => 'web',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/permissions/data/admin/{$uuid}");

        $response->assertStatus(200)
            ->assertJsonPath('data.uuid', $permission->uuid);
    }

    public function test_admin_can_update_permission(): void
    {
        $permission = PermissionEloquentModel::query()->create([
            'uuid' => $uuid = fake()->uuid(),
            'name' => 'SYNC_REPORTS',
            'guard_name' => 'web',
        ]);

        $response = $this->actingAs($this->admin)
            ->putJson("/permissions/data/admin/{$uuid}", [
                'name' => 'SYNC_REPORTS_UPDATED',
                'guard_name' => 'web',
                'roles' => ['SUPER_ADMIN'],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'SYNC_REPORTS_UPDATED');
    }

    public function test_admin_can_delete_permission(): void
    {
        $permission = PermissionEloquentModel::query()->create([
            'uuid' => $uuid = fake()->uuid(),
            'name' => 'TEMP_PERMISSION',
            'guard_name' => 'web',
        ]);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/permissions/data/admin/{$uuid}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('permissions', ['uuid' => $uuid]);
    }
}
