<?php

declare(strict_types=1);

namespace Modules\Authorization\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Authorization\Infrastructure\Persistence\Eloquent\Models\Role;
use Tests\TestCase;

final class RoleManagementTest extends TestCase
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

    public function test_super_admin_creates_a_role_with_permissions(): void
    {
        $this->actingAs($this->superAdmin())
            ->post('/roles', [
                'name' => 'CONTENT_EDITOR',
                'permissions' => ['VIEW_ROLES', 'CREATE_ROLES'],
            ])
            ->assertRedirect();

        $role = Role::query()->where('name', 'CONTENT_EDITOR')->firstOrFail();

        $this->assertTrue($role->hasPermissionTo('VIEW_ROLES'));
        $this->assertTrue($role->hasPermissionTo('CREATE_ROLES'));
        $this->assertNotNull($role->uuid);
    }

    public function test_duplicate_role_name_is_rejected(): void
    {
        $this->actingAs($this->superAdmin())
            ->post('/roles', ['name' => 'ADMIN'])
            ->assertSessionHasErrors('name');
    }

    public function test_unknown_permission_is_rejected(): void
    {
        $this->actingAs($this->superAdmin())
            ->post('/roles', ['name' => 'BROKEN', 'permissions' => ['DOES_NOT_EXIST']])
            ->assertSessionHasErrors('permissions.0');
    }

    public function test_update_resyncs_permissions(): void
    {
        $admin = $this->superAdmin();
        $this->actingAs($admin)->post('/roles', ['name' => 'REVIEWER', 'permissions' => ['VIEW_ROLES']])->assertRedirect();

        $role = Role::query()->where('name', 'REVIEWER')->firstOrFail();

        $this->actingAs($admin)
            ->put("/roles/{$role->uuid}", ['name' => 'REVIEWER', 'permissions' => ['CREATE_ROLES']])
            ->assertRedirect();

        $role->refresh();
        $this->assertFalse($role->hasPermissionTo('VIEW_ROLES'));
        $this->assertTrue($role->hasPermissionTo('CREATE_ROLES'));
    }

    public function test_a_protected_system_role_cannot_be_renamed(): void
    {
        $admin = $this->superAdmin();
        $role = Role::query()->where('name', 'ADMIN')->firstOrFail();

        $this->actingAs($admin)
            ->put("/roles/{$role->uuid}", ['name' => 'ADMIN_RENAMED'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('roles', ['uuid' => $role->uuid, 'name' => 'ADMIN']);
        $this->assertDatabaseMissing('roles', ['name' => 'ADMIN_RENAMED']);
    }

    public function test_a_protected_system_role_cannot_be_deleted(): void
    {
        $admin = $this->superAdmin();
        $role = Role::query()->where('name', 'SUPER_ADMIN')->firstOrFail();

        $this->actingAs($admin)
            ->delete("/roles/{$role->uuid}")
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('roles', ['uuid' => $role->uuid, 'deleted_at' => null]);
    }

    public function test_delete_then_restore_a_role(): void
    {
        $admin = $this->superAdmin();
        $role = Role::factory()->create();

        $this->actingAs($admin)->delete("/roles/{$role->uuid}")->assertRedirect();
        $this->assertSoftDeleted('roles', ['uuid' => $role->uuid]);

        $this->actingAs($admin)->post("/roles/{$role->uuid}/restore")->assertRedirect();
        $this->assertDatabaseHas('roles', ['uuid' => $role->uuid, 'deleted_at' => null]);
    }

    public function test_bulk_delete_then_restore(): void
    {
        $admin = $this->superAdmin();
        $uuids = Role::factory()->count(3)->create()->pluck('uuid')->all();

        $this->actingAs($admin)->post('/roles/bulk-delete', ['uuids' => $uuids])->assertRedirect();
        foreach ($uuids as $uuid) {
            $this->assertSoftDeleted('roles', ['uuid' => $uuid]);
        }

        $this->actingAs($admin)->post('/roles/bulk-restore', ['uuids' => $uuids])->assertRedirect();
        foreach ($uuids as $uuid) {
            $this->assertDatabaseHas('roles', ['uuid' => $uuid, 'deleted_at' => null]);
        }
    }

    public function test_bulk_delete_rejects_a_protected_role_in_the_batch(): void
    {
        $admin = $this->superAdmin();
        $free = Role::factory()->create();
        $protected = Role::query()->where('name', 'MODERATOR')->firstOrFail();

        $this->actingAs($admin)
            ->post('/roles/bulk-delete', ['uuids' => [$free->uuid, $protected->uuid]])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('roles', ['uuid' => $free->uuid, 'deleted_at' => null]);
    }

    public function test_bulk_delete_rejects_more_than_500_uuids(): void
    {
        $uuids = array_map(static fn (): string => (string) Str::uuid(), range(1, 501));

        $this->actingAs($this->superAdmin())
            ->postJson('/roles/bulk-delete', ['uuids' => $uuids])
            ->assertStatus(422)
            ->assertJsonValidationErrors('uuids');
    }

    public function test_search_filter_narrows_the_list(): void
    {
        Role::factory()->create(['name' => 'BILLING_MANAGER']);
        Role::factory()->create(['name' => 'WAREHOUSE_CLERK']);

        $this->actingAs($this->superAdmin())
            ->getJson('/roles?search=BILLING')
            ->assertOk()
            ->assertJsonFragment(['name' => 'BILLING_MANAGER'])
            ->assertJsonMissing(['name' => 'WAREHOUSE_CLERK']);
    }

    public function test_a_user_without_permission_cannot_manage_roles(): void
    {
        $plain = User::factory()->create();
        $plain->assignRole('USER');

        $this->actingAs($plain)->get('/roles')->assertForbidden();
        $this->actingAs($plain)->post('/roles', ['name' => 'X_ROLE'])->assertForbidden();
    }
}
