<?php

declare(strict_types=1);

namespace Modules\Users\Tests\Unit;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Users\Domain\AssignableAccess;
use Modules\Users\Domain\Exceptions\PrivilegeEscalationException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Unit coverage for the anti-privilege-escalation invariant. Spatie's role /
 * permission resolution needs the DB, so this seeds the foundation matrix and
 * asserts the pure domain rule directly (not through the HTTP layer).
 */
final class AssignableAccessTest extends TestCase
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

    public function test_an_empty_role_set_is_always_allowed(): void
    {
        $plain = User::factory()->create();
        $plain->assignRole('USER');

        AssignableAccess::assertRolesAllowed($plain, []);

        $this->expectNotToPerformAssertions();
    }

    public function test_super_admin_may_assign_any_role(): void
    {
        AssignableAccess::assertRolesAllowed($this->superAdmin(), ['SUPER_ADMIN', 'ADMIN', 'USER']);

        $this->expectNotToPerformAssertions();
    }

    public function test_an_actor_may_assign_a_role_they_hold(): void
    {
        $delegate = User::factory()->create();
        $delegate->assignRole('ADMIN');

        AssignableAccess::assertRolesAllowed($delegate, ['ADMIN']);

        $this->expectNotToPerformAssertions();
    }

    public function test_an_actor_cannot_assign_a_role_they_do_not_hold(): void
    {
        $delegate = User::factory()->create();
        $delegate->assignRole('USER');

        $this->expectException(PrivilegeEscalationException::class);
        $this->expectExceptionMessage('SUPER_ADMIN');

        AssignableAccess::assertRolesAllowed($delegate, ['SUPER_ADMIN']);
    }

    /* ── Permissions ───────────────────────────────────────────────────────── */

    public function test_an_empty_permission_set_is_always_allowed(): void
    {
        $plain = User::factory()->create();
        $plain->assignRole('USER');

        AssignableAccess::assertPermissionsAllowed($plain, []);

        $this->expectNotToPerformAssertions();
    }

    public function test_super_admin_may_grant_any_permission(): void
    {
        AssignableAccess::assertPermissionsAllowed($this->superAdmin(), ['DELETE_USERS', 'EXPORT_USERS']);

        $this->expectNotToPerformAssertions();
    }

    public function test_an_actor_may_grant_a_permission_they_hold(): void
    {
        $delegate = $this->delegateWith(['VIEW_USERS']);

        AssignableAccess::assertPermissionsAllowed($delegate, ['VIEW_USERS']);

        $this->expectNotToPerformAssertions();
    }

    public function test_an_actor_cannot_grant_a_permission_they_do_not_hold(): void
    {
        $delegate = $this->delegateWith(['VIEW_USERS']);

        $this->expectException(PrivilegeEscalationException::class);
        $this->expectExceptionMessage('DELETE_USERS');

        AssignableAccess::assertPermissionsAllowed($delegate, ['DELETE_USERS']);
    }
}
