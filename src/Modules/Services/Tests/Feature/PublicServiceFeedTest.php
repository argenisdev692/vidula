<?php

declare(strict_types=1);

namespace Modules\Services\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Services\Infrastructure\Persistence\Eloquent\Models\ServiceEloquentModel;
use Tests\TestCase;

final class PublicServiceFeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_feed_only_returns_active_services(): void
    {
        ServiceEloquentModel::factory()->create(['name' => 'Active Service', 'is_active' => true]);
        ServiceEloquentModel::factory()->create(['name' => 'Inactive Service', 'is_active' => false]);

        $this->getJson('/api/services/public')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Active Service'])
            ->assertJsonMissing(['name' => 'Inactive Service']);
    }

    public function test_public_feed_requires_no_authentication(): void
    {
        ServiceEloquentModel::factory()->create(['is_active' => true]);

        $this->getJson('/api/services/public')->assertOk();
    }

    public function test_public_feed_does_not_expose_internal_ids(): void
    {
        ServiceEloquentModel::factory()->create(['is_active' => true]);

        $this->getJson('/api/services/public')->assertOk()->assertJsonMissingPath('data.0.id');
    }

    public function test_creating_a_service_busts_the_cached_feed(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');

        // Warm the cache with an empty feed.
        $this->getJson('/api/services/public')->assertOk()->assertJsonCount(0, 'data');

        $this->actingAs($admin)->post('/services', [
            'name' => 'Freshly Added',
            'slug' => 'freshly_added',
            'is_active' => true,
        ])->assertRedirect();

        $this->getJson('/api/services/public')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Freshly Added']);
    }

    public function test_deactivating_a_service_busts_the_cached_feed(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');
        $service = ServiceEloquentModel::factory()->create(['name' => 'Visible For Now', 'slug' => 'visible_for_now', 'is_active' => true]);

        // Warm the cache while the service is still active.
        $this->getJson('/api/services/public')->assertOk()->assertJsonFragment(['name' => 'Visible For Now']);

        $this->actingAs($admin)->put("/services/{$service->uuid}", [
            'name' => 'Visible For Now',
            'slug' => 'visible_for_now',
            'is_active' => false,
        ])->assertRedirect();

        $this->getJson('/api/services/public')
            ->assertOk()
            ->assertJsonMissing(['name' => 'Visible For Now']);
    }
}
