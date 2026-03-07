<?php

declare(strict_types=1);

namespace Modules\Roles\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Permissions\Infrastructure\Persistence\Eloquent\Models\PermissionEloquentModel;
use Modules\Roles\Infrastructure\Persistence\Eloquent\Models\RoleEloquentModel;
use Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserEloquentModel;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

final class RoleCrudFeatureTest extends TestCase
{
    use RefreshDatabase;

    private UserEloquentModel $admin;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['VIEW_ROLES', 'CREATE_ROLES', 'UPDATE_ROLES', 'DELETE_ROLES'] as $permission) {
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
            'email' => 'admin-roles@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $this->admin->assignRole($superAdminRole);
    }

    public function test_admin_can_list_roles(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/roles/data/admin');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['total', 'perPage', 'currentPage', 'lastPage'],
            ]);
    }

    public function test_admin_can_create_role(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/roles/data/admin', [
                'name' => 'MANAGER',
                'guard_name' => 'web',
                'permissions' => ['VIEW_ROLES'],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'MANAGER');
    }

    public function test_admin_can_show_role(): void
    {
        $role = RoleEloquentModel::query()->create([
            'uuid' => $uuid = fake()->uuid(),
            'name' => 'EDITOR',
            'guard_name' => 'web',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/roles/data/admin/{$uuid}");

        $response->assertStatus(200)
            ->assertJsonPath('data.uuid', $role->uuid);
    }

    public function test_admin_can_update_role(): void
    {
        $role = RoleEloquentModel::query()->create([
            'uuid' => $uuid = fake()->uuid(),
            'name' => 'AUTHOR',
            'guard_name' => 'web',
        ]);

        $response = $this->actingAs($this->admin)
            ->putJson("/roles/data/admin/{$uuid}", [
                'name' => 'AUTHOR_UPDATED',
                'guard_name' => 'web',
                'permissions' => ['VIEW_ROLES', 'UPDATE_ROLES'],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'AUTHOR_UPDATED');
    }

    public function test_admin_can_delete_role(): void
    {
        $role = RoleEloquentModel::query()->create([
            'uuid' => $uuid = fake()->uuid(),
            'name' => 'TO_DELETE',
            'guard_name' => 'web',
        ]);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/roles/data/admin/{$uuid}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('roles', ['uuid' => $uuid]);
    }

    public function test_super_admin_role_cannot_be_deleted(): void
    {
        $role = RoleEloquentModel::query()->where('name', 'SUPER_ADMIN')->firstOrFail();

        $response = $this->actingAs($this->admin)
            ->deleteJson("/roles/data/admin/{$role->uuid}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('roles', ['uuid' => $role->uuid]);
    }
}
