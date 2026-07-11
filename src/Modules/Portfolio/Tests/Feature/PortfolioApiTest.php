<?php

declare(strict_types=1);

namespace Modules\Portfolio\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Portfolio\Infrastructure\Persistence\Eloquent\Models\PortfolioEloquentModel;
use Tests\TestCase;

/**
 * Covers the secondary Sanctum-authenticated API surface (the primary UI is
 * Inertia/web). The unauthenticated public feed is exercised separately in
 * {@see PublicPortfolioFeedTest}; here we assert the 401/403/200 matrix that
 * OWASP §11/§13 requires for every exposed authenticated endpoint.
 */
final class PortfolioApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_authenticated_api_lists_portfolios_with_permission(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');
        PortfolioEloquentModel::factory()->create(['title' => 'Api Listed']);

        Sanctum::actingAs($admin);

        $this->getJson('/api/portfolios')
            ->assertOk()
            ->assertJsonFragment(['title' => 'Api Listed']);
    }

    public function test_authenticated_api_shows_a_portfolio_with_permission(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');
        $portfolio = PortfolioEloquentModel::factory()->create();

        Sanctum::actingAs($admin);

        $this->getJson("/api/portfolios/{$portfolio->uuid}")
            ->assertOk()
            ->assertJsonPath('data.uuid', $portfolio->uuid);
    }

    public function test_api_requires_authentication(): void
    {
        $this->getJson('/api/portfolios')->assertUnauthorized();
    }

    public function test_api_forbids_users_without_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole('USER');

        Sanctum::actingAs($user);

        $this->getJson('/api/portfolios')->assertForbidden();
    }
}
