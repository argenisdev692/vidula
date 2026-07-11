<?php

declare(strict_types=1);

namespace Modules\Portfolio\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Portfolio\Infrastructure\Persistence\Eloquent\Models\PortfolioEloquentModel;
use Tests\TestCase;

final class PortfolioGalleryTest extends TestCase
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

    public function test_gallery_image_is_added_to_a_portfolio(): void
    {
        Storage::fake('r2');
        $admin = $this->superAdmin();
        $portfolio = PortfolioEloquentModel::factory()->create();

        $this->actingAs($admin)
            ->post("/portfolios/{$portfolio->uuid}/gallery", [
                'image' => UploadedFile::fake()->image('shot-1.png', 800, 600),
            ])
            ->assertRedirect();

        $this->assertCount(1, $portfolio->gallery()->get());
        Storage::disk('r2')->assertExists($portfolio->gallery()->first()->path);
    }

    public function test_gallery_image_is_removed_and_deleted_from_r2(): void
    {
        Storage::fake('r2');
        $admin = $this->superAdmin();
        $portfolio = PortfolioEloquentModel::factory()->create();

        $this->actingAs($admin)->post("/portfolios/{$portfolio->uuid}/gallery", [
            'image' => UploadedFile::fake()->image('shot-1.png', 800, 600),
        ])->assertRedirect();

        $media = $portfolio->gallery()->firstOrFail();
        $path = $media->path;

        $this->actingAs($admin)
            ->delete("/portfolios/{$portfolio->uuid}/gallery/{$media->uuid}")
            ->assertRedirect();

        $this->assertDatabaseMissing('portfolio_media', ['uuid' => $media->uuid, 'deleted_at' => null]);
        Storage::disk('r2')->assertMissing($path);
    }

    public function test_gallery_can_be_reordered(): void
    {
        Storage::fake('r2');
        $admin = $this->superAdmin();
        $portfolio = PortfolioEloquentModel::factory()->create();

        $first = $portfolio->gallery()->create(['path' => 'portfolios/gallery/a.jpg', 'sort_order' => 0]);
        $second = $portfolio->gallery()->create(['path' => 'portfolios/gallery/b.jpg', 'sort_order' => 1]);

        $this->actingAs($admin)
            ->post("/portfolios/{$portfolio->uuid}/gallery/reorder", [
                'uuids' => [$second->uuid, $first->uuid],
            ])
            ->assertRedirect();

        $this->assertSame(0, $second->refresh()->sort_order);
        $this->assertSame(1, $first->refresh()->sort_order);
    }

    public function test_a_user_without_permission_cannot_manage_the_gallery(): void
    {
        $plain = User::factory()->create();
        $plain->assignRole('USER');
        $portfolio = PortfolioEloquentModel::factory()->create();

        $this->actingAs($plain)
            ->post("/portfolios/{$portfolio->uuid}/gallery", [
                'image' => UploadedFile::fake()->image('shot.png'),
            ])
            ->assertForbidden();
    }
}
