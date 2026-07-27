<?php

declare(strict_types=1);

namespace Modules\Authorization\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Authorization\Infrastructure\Persistence\Eloquent\Models\Permission;
use Modules\Authorization\Infrastructure\Persistence\Eloquent\Models\Role;
use Tests\TestCase;

final class RolePermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_restores_soft_deleted_resume_studio_permission_and_grants_super_admin(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $permission = Permission::query()
            ->where('name', 'VIEW_ANY_RESUME_STUDIOS')
            ->where('guard_name', 'web')
            ->firstOrFail();

        $permission->delete();

        $this->assertSoftDeleted('permissions', ['id' => $permission->id]);

        $this->seed(RolePermissionSeeder::class);

        $this->assertDatabaseHas('permissions', [
            'id' => $permission->id,
            'name' => 'VIEW_ANY_RESUME_STUDIOS',
            'deleted_at' => null,
        ]);

        $superAdminRole = Role::query()->where('name', 'SUPER_ADMIN')->firstOrFail();
        $this->assertTrue($superAdminRole->hasPermissionTo('VIEW_ANY_RESUME_STUDIOS'));

        $user = User::factory()->create();
        $user->assignRole('SUPER_ADMIN');

        $this->actingAs($user)->get('/resume-studio')->assertOk();
    }

    public function test_seeder_restores_soft_deleted_super_admin_role_instead_of_duplicating(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $role = Role::query()->where('name', 'SUPER_ADMIN')->firstOrFail();
        $originalId = $role->id;
        $role->delete();

        $this->seed(RolePermissionSeeder::class);

        $this->assertDatabaseHas('roles', [
            'id' => $originalId,
            'name' => 'SUPER_ADMIN',
            'deleted_at' => null,
        ]);
        $this->assertSame(1, Role::withTrashed()->where('name', 'SUPER_ADMIN')->count());

        $user = User::factory()->create();
        $user->assignRole('SUPER_ADMIN');
        $this->assertTrue($user->can('VIEW_ANY_RESUME_STUDIOS'));
    }
}
