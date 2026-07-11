<?php

declare(strict_types=1);

namespace Modules\Services\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Services\Infrastructure\Persistence\Eloquent\Models\ServiceEloquentModel;
use Tests\TestCase;

final class ServiceManagementTest extends TestCase
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

    public function test_super_admin_creates_a_service(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->post('/services', [
                'name' => 'E-commerce',
                'slug' => 'ecommerce',
                'description' => 'Online store with catalog, cart and checkout.',
                'is_active' => true,
            ])
            ->assertRedirect();

        $service = ServiceEloquentModel::query()->where('slug', 'ecommerce')->firstOrFail();

        $this->assertSame($admin->id, $service->user_id);
        $this->assertSame('E-commerce', $service->name);
    }

    public function test_duplicate_slug_is_rejected(): void
    {
        ServiceEloquentModel::factory()->create(['slug' => 'branding']);

        $this->actingAs($this->superAdmin())
            ->post('/services', ['name' => 'Branding', 'slug' => 'branding'])
            ->assertSessionHasErrors('slug');
    }

    public function test_update_changes_the_name_and_keeps_the_slug(): void
    {
        $admin = $this->superAdmin();
        $service = ServiceEloquentModel::factory()->create(['name' => 'Old Name', 'slug' => 'stable-slug']);

        $this->actingAs($admin)->put("/services/{$service->uuid}", [
            'name' => 'New Name',
            'slug' => 'stable-slug',
        ])->assertRedirect();

        $service->refresh();
        $this->assertSame('New Name', $service->name);
        $this->assertSame('stable-slug', $service->slug);
    }

    public function test_delete_then_restore_a_service(): void
    {
        $admin = $this->superAdmin();
        $service = ServiceEloquentModel::factory()->create();

        $this->actingAs($admin)->delete("/services/{$service->uuid}")->assertRedirect();
        $this->assertSoftDeleted('services', ['uuid' => $service->uuid]);

        $this->actingAs($admin)->post("/services/{$service->uuid}/restore")->assertRedirect();
        $this->assertDatabaseHas('services', ['uuid' => $service->uuid, 'deleted_at' => null]);
    }

    public function test_bulk_delete_then_restore(): void
    {
        $admin = $this->superAdmin();
        $uuids = ServiceEloquentModel::factory()->count(3)->create()->pluck('uuid')->all();

        $this->actingAs($admin)->post('/services/bulk-delete', ['uuids' => $uuids])->assertRedirect();
        foreach ($uuids as $uuid) {
            $this->assertSoftDeleted('services', ['uuid' => $uuid]);
        }

        $this->actingAs($admin)->post('/services/bulk-restore', ['uuids' => $uuids])->assertRedirect();
        foreach ($uuids as $uuid) {
            $this->assertDatabaseHas('services', ['uuid' => $uuid, 'deleted_at' => null]);
        }
    }

    public function test_bulk_delete_rejects_more_than_500_uuids(): void
    {
        $uuids = array_map(static fn (): string => (string) Str::uuid(), range(1, 501));

        $this->actingAs($this->superAdmin())
            ->postJson('/services/bulk-delete', ['uuids' => $uuids])
            ->assertStatus(422)
            ->assertJsonValidationErrors('uuids');
    }

    public function test_search_filter_narrows_the_list(): void
    {
        ServiceEloquentModel::factory()->create(['name' => 'Artificial Intelligence Consulting']);
        ServiceEloquentModel::factory()->create(['name' => 'Gardening Service']);

        $this->actingAs($this->superAdmin())
            ->getJson('/services?search=Intelligence')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Artificial Intelligence Consulting'])
            ->assertJsonMissing(['name' => 'Gardening Service']);
    }

    public function test_a_user_without_permission_cannot_manage_services(): void
    {
        $plain = User::factory()->create();
        $plain->assignRole('USER');

        $this->actingAs($plain)->get('/services')->assertForbidden();
        $this->actingAs($plain)->post('/services', ['name' => 'X', 'slug' => 'x'])->assertForbidden();
    }
}
