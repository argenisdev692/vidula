<?php

declare(strict_types=1);

namespace Modules\Authorization\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Authorization\Infrastructure\Persistence\Eloquent\Models\Permission;
use Tests\TestCase;

final class PermissionManagementTest extends TestCase
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

    public function test_super_admin_creates_a_permission(): void
    {
        $this->actingAs($this->superAdmin())
            ->post('/permissions', ['name' => 'VIEW_ANY_INVOICES'])
            ->assertRedirect();

        $this->assertDatabaseHas('permissions', ['name' => 'VIEW_ANY_INVOICES', 'guard_name' => 'web']);
    }

    public function test_duplicate_permission_name_is_rejected(): void
    {
        $this->actingAs($this->superAdmin())
            ->post('/permissions', ['name' => 'VIEW_ROLES'])
            ->assertSessionHasErrors('name');
    }

    public function test_lower_case_permission_name_is_rejected(): void
    {
        $this->actingAs($this->superAdmin())
            ->post('/permissions', ['name' => 'view_invoices'])
            ->assertSessionHasErrors('name');
    }

    public function test_update_a_permission_name(): void
    {
        $admin = $this->superAdmin();
        $permission = Permission::factory()->create();

        $this->actingAs($admin)
            ->put("/permissions/{$permission->uuid}", ['name' => 'RENAMED_PERMISSION'])
            ->assertRedirect();

        $this->assertDatabaseHas('permissions', ['uuid' => $permission->uuid, 'name' => 'RENAMED_PERMISSION']);
    }

    public function test_delete_then_restore_a_permission(): void
    {
        $admin = $this->superAdmin();
        $permission = Permission::factory()->create();

        $this->actingAs($admin)->delete("/permissions/{$permission->uuid}")->assertRedirect();
        $this->assertSoftDeleted('permissions', ['uuid' => $permission->uuid]);

        $this->actingAs($admin)->post("/permissions/{$permission->uuid}/restore")->assertRedirect();
        $this->assertDatabaseHas('permissions', ['uuid' => $permission->uuid, 'deleted_at' => null]);
    }

    public function test_bulk_delete_then_restore(): void
    {
        $admin = $this->superAdmin();
        $uuids = Permission::factory()->count(3)->create()->pluck('uuid')->all();

        $this->actingAs($admin)->post('/permissions/bulk-delete', ['uuids' => $uuids])->assertRedirect();
        foreach ($uuids as $uuid) {
            $this->assertSoftDeleted('permissions', ['uuid' => $uuid]);
        }

        $this->actingAs($admin)->post('/permissions/bulk-restore', ['uuids' => $uuids])->assertRedirect();
        foreach ($uuids as $uuid) {
            $this->assertDatabaseHas('permissions', ['uuid' => $uuid, 'deleted_at' => null]);
        }
    }

    public function test_bulk_delete_rejects_more_than_500_uuids(): void
    {
        $uuids = array_map(static fn (): string => (string) Str::uuid(), range(1, 501));

        $this->actingAs($this->superAdmin())
            ->postJson('/permissions/bulk-delete', ['uuids' => $uuids])
            ->assertStatus(422)
            ->assertJsonValidationErrors('uuids');
    }

    public function test_search_filter_narrows_the_list(): void
    {
        Permission::factory()->create(['name' => 'MANAGE_WIDGETS']);
        Permission::factory()->create(['name' => 'MANAGE_GADGETS']);

        $this->actingAs($this->superAdmin())
            ->getJson('/permissions?search=WIDGETS')
            ->assertOk()
            ->assertJsonFragment(['name' => 'MANAGE_WIDGETS'])
            ->assertJsonMissing(['name' => 'MANAGE_GADGETS']);
    }

    public function test_a_user_without_permission_cannot_manage_permissions(): void
    {
        $plain = User::factory()->create();
        $plain->assignRole('USER');

        $this->actingAs($plain)->get('/permissions')->assertForbidden();
        $this->actingAs($plain)->post('/permissions', ['name' => 'HACK_PERMISSION'])->assertForbidden();
    }
}
