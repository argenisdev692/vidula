<?php

declare(strict_types=1);

namespace Modules\Portfolio\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\Portfolio\Infrastructure\Persistence\Eloquent\Models\PortfolioEloquentModel;
use Tests\TestCase;

final class PublicPortfolioFeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_feed_only_returns_is_public_portfolios(): void
    {
        PortfolioEloquentModel::factory()->create(['title' => 'Public Project', 'is_public' => true]);
        PortfolioEloquentModel::factory()->create(['title' => 'Private Project', 'is_public' => false]);

        $this->getJson('/api/portfolios/public')
            ->assertOk()
            ->assertJsonFragment(['title' => 'Public Project'])
            ->assertJsonMissing(['title' => 'Private Project']);
    }

    public function test_public_feed_requires_no_authentication(): void
    {
        PortfolioEloquentModel::factory()->create(['is_public' => true]);

        $this->getJson('/api/portfolios/public')->assertOk();
    }

    public function test_public_feed_does_not_expose_internal_ids(): void
    {
        PortfolioEloquentModel::factory()->create(['is_public' => true]);

        $response = $this->getJson('/api/portfolios/public')->assertOk();

        $response->assertJsonMissingPath('data.0.id');
    }

    public function test_public_feed_shape_is_the_readmodel_allowlist(): void
    {
        Storage::fake('r2');
        $portfolio = PortfolioEloquentModel::factory()->create([
            'is_public' => true,
            'cover_path' => 'portfolios/cover/x.png',
        ]);
        $portfolio->gallery()->create(['path' => 'portfolios/gallery/a.jpg', 'sort_order' => 0]);

        $response = $this->getJson('/api/portfolios/public')->assertOk();

        // Gallery items expose uuid + url + sort_order...
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'uuid', 'title', 'client_name', 'cover_url',
                    'gallery' => ['*' => ['uuid', 'url', 'sort_order']],
                ],
            ],
        ]);
        // ...never the raw R2 object key, the internal id, or the author relation.
        $response->assertJsonMissingPath('data.0.gallery.0.path');
        $response->assertJsonMissingPath('data.0.user');
        $response->assertJsonMissingPath('data.0.user_id');
    }

    public function test_creating_a_portfolio_busts_the_cached_feed(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');

        // Warm the cache with an empty feed.
        $this->getJson('/api/portfolios/public')->assertOk()->assertJsonCount(0, 'data');

        $this->actingAs($admin)->post('/portfolios', [
            'title' => 'Freshly Published',
            'client_name' => 'Client',
            'project_type' => 'web',
            'is_public' => true,
        ])->assertRedirect();

        $this->getJson('/api/portfolios/public')
            ->assertOk()
            ->assertJsonFragment(['title' => 'Freshly Published']);
    }

    public function test_unpublishing_a_portfolio_busts_the_cached_feed(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');
        $portfolio = PortfolioEloquentModel::factory()->create(['title' => 'Visible For Now', 'is_public' => true]);

        // Warm the cache while the portfolio is still public.
        $this->getJson('/api/portfolios/public')->assertOk()->assertJsonFragment(['title' => 'Visible For Now']);

        $this->actingAs($admin)->put("/portfolios/{$portfolio->uuid}", [
            'title' => 'Visible For Now',
            'client_name' => $portfolio->client_name,
            'project_type' => $portfolio->project_type,
            'is_public' => false,
        ])->assertRedirect();

        $this->getJson('/api/portfolios/public')
            ->assertOk()
            ->assertJsonMissing(['title' => 'Visible For Now']);
    }
}
