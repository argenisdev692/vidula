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

    /* ── Roles ─────────────────────────────────────────────────────────────── */

    public function test_super_admin_syncs_roles(): void
    {
        $admin = $this->superAdmin();
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->put("/users/{$target->uuid}/roles", ['roles' => ['USER']])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue($target->refresh()->hasRole('USER'));
    }

    public function test_role_sync_is_a_replace_not_a_merge(): void
    {
        $admin = $this->superAdmin();
        $target = User::factory()->create();
        $target->assignRole('ADMIN');

        $this->actingAs($admin)
            ->put("/users/{$target->uuid}/roles", ['roles' => ['USER']])
            ->assertRedirect();

        $target->refresh();
        $this->assertTrue($target->hasRole('USER'));
        $this->assertFalse($target->hasRole('ADMIN'), 'Roles are synced, not merged.');
    }

    public function test_roles_route_is_forbidden_without_assign_roles(): void
    {
        $plain = User::factory()->create();
        $plain->assignRole('USER');
        $target = User::factory()->create();

        $this->actingAs($plain)
            ->put("/users/{$target->uuid}/roles", ['roles' => ['USER']])
            ->assertForbidden();
    }

    public function test_a_delegate_cannot_assign_a_role_they_do_not_hold(): void
    {
        $delegate = $this->delegateWith(['ASSIGN_ROLES_USERS']);
        $target = User::factory()->create();

        $this->actingAs($delegate)
            ->put("/users/{$target->uuid}/roles", ['roles' => ['ADMIN']])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertFalse($target->refresh()->hasRole('ADMIN'), 'Escalation must be blocked.');
    }

    public function test_a_user_may_hold_at_most_one_role(): void
    {
        $admin = $this->superAdmin();
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->put("/users/{$target->uuid}/roles", ['roles' => ['USER', 'ADMIN']])
            ->assertSessionHasErrors('roles');

        $this->assertFalse($target->refresh()->hasAnyRole(['USER', 'ADMIN']), 'A rejected payload assigns nothing.');
    }

    /* ── Access screen (role + direct permissions) ─────────────────────────── */

    public function test_access_screen_is_reachable_with_permission(): void
    {
        $admin = $this->superAdmin();
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->get("/users/{$target->uuid}/permissions")
            ->assertOk();
    }

    public function test_a_role_only_delegate_can_reach_the_access_screen(): void
    {
        $delegate = $this->delegateWith(['ASSIGN_ROLES_USERS']);
        $target = User::factory()->create();

        $this->actingAs($delegate)
            ->get("/users/{$target->uuid}/permissions")
            ->assertOk();
    }

    public function test_access_screen_is_forbidden_without_any_assign_permission(): void
    {
        $plain = User::factory()->create();
        $plain->assignRole('USER');
        $target = User::factory()->create();

        $this->actingAs($plain)
            ->get("/users/{$target->uuid}/permissions")
            ->assertForbidden();
    }

    public function test_super_admin_grants_then_revokes_a_direct_permission(): void
    {
        $admin = $this->superAdmin();
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->put("/users/{$target->uuid}/permissions", ['permission' => 'VIEW_USERS', 'granted' => true])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue($target->refresh()->hasDirectPermission('VIEW_USERS'));

        $this->actingAs($admin)
            ->put("/users/{$target->uuid}/permissions", ['permission' => 'VIEW_USERS', 'granted' => false])
            ->assertRedirect();

        $this->assertFalse($target->refresh()->hasDirectPermission('VIEW_USERS'));
    }

    public function test_a_delegate_cannot_grant_a_permission_they_do_not_hold(): void
    {
        $delegate = $this->delegateWith(['ASSIGN_PERMISSIONS_USERS']);
        $target = User::factory()->create();

        $this->actingAs($delegate)
            ->put("/users/{$target->uuid}/permissions", ['permission' => 'DELETE_USERS', 'granted' => true])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertFalse($target->refresh()->hasDirectPermission('DELETE_USERS'));
    }

    /* ── Invite ────────────────────────────────────────────────────────────── */

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
