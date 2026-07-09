<?php

declare(strict_types=1);

namespace Modules\Users\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class UserAccessTest extends TestCase
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
     * @param  list<string>  $permissions
     */
    private function delegateWith(array $permissions): User
    {
        $role = Role::firstOrCreate(['name' => 'DELEGATE', 'guard_name' => 'web']);
        $role->givePermissionTo($permissions);

        $user = User::factory()->create();
        $user->assignRole('DELEGATE');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    public function test_super_admin_syncs_roles_and_direct_permissions(): void
    {
        $admin = $this->superAdmin();
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->put("/users/{$target->uuid}/access", [
                'roles' => ['USER'],
                'direct_permissions' => ['VIEW_USERS', 'UPDATE_USERS'],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $target->refresh();
        $this->assertTrue($target->hasRole('USER'));
        $this->assertTrue($target->hasDirectPermission('VIEW_USERS'));
        $this->assertTrue($target->hasDirectPermission('UPDATE_USERS'));
    }

    public function test_sync_is_a_replace_not_a_merge(): void
    {
        $admin = $this->superAdmin();
        $target = User::factory()->create();
        $target->assignRole('ADMIN');
        $target->givePermissionTo('DELETE_USERS');

        $this->actingAs($admin)
            ->put("/users/{$target->uuid}/access", ['roles' => ['USER'], 'direct_permissions' => []])
            ->assertRedirect();

        $target->refresh();
        $this->assertTrue($target->hasRole('USER'));
        $this->assertFalse($target->hasRole('ADMIN'), 'Roles are synced, not merged.');
        $this->assertFalse($target->hasDirectPermission('DELETE_USERS'), 'Direct grants are synced, not merged.');
    }

    public function test_access_route_is_forbidden_without_the_assign_permissions(): void
    {
        $plain = User::factory()->create();
        $plain->assignRole('USER');
        $target = User::factory()->create();

        $this->actingAs($plain)
            ->put("/users/{$target->uuid}/access", ['roles' => ['USER'], 'direct_permissions' => []])
            ->assertForbidden();
    }

    public function test_a_delegate_cannot_assign_a_role_they_do_not_hold(): void
    {
        $delegate = $this->delegateWith(['ASSIGN_ROLES_USERS', 'ASSIGN_PERMISSIONS_USERS']);
        $target = User::factory()->create();

        $this->actingAs($delegate)
            ->put("/users/{$target->uuid}/access", ['roles' => ['ADMIN'], 'direct_permissions' => []])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertFalse($target->refresh()->hasRole('ADMIN'), 'Escalation must be blocked.');
    }

    public function test_a_delegate_cannot_grant_a_permission_they_do_not_hold(): void
    {
        $delegate = $this->delegateWith(['ASSIGN_ROLES_USERS', 'ASSIGN_PERMISSIONS_USERS']);
        $target = User::factory()->create();

        $this->actingAs($delegate)
            ->put("/users/{$target->uuid}/access", ['roles' => [], 'direct_permissions' => ['DELETE_USERS']])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertFalse($target->refresh()->hasDirectPermission('DELETE_USERS'));
    }

    public function test_invite_assigns_the_selected_roles(): void
    {
        Notification::fake();
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->post('/users', [
                'first_name' => 'Role',
                'email' => 'role.holder@example.com',
                'roles' => ['USER'],
            ])
            ->assertRedirect();

        $user = User::query()->where('email', 'role.holder@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('USER'));
    }
}
