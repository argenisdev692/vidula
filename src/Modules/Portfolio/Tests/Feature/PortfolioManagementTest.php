<?php

declare(strict_types=1);

namespace Modules\Portfolio\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Portfolio\Infrastructure\Persistence\Eloquent\Models\PortfolioEloquentModel;
use Tests\TestCase;

final class PortfolioManagementTest extends TestCase
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

    public function test_super_admin_creates_a_portfolio_with_cover_and_video(): void
    {
        Storage::fake('r2');
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->post('/portfolios', [
                'title' => 'Acme Rebrand',
                'client_name' => 'Acme Corp',
                'project_type' => 'branding',
                'tech_stack' => ['React', 'Next.js', 'PostgreSQL', 'Stripe'],
                'live_url' => 'https://acme.example.com',
                'is_public' => true,
                'cover' => UploadedFile::fake()->image('cover.png', 800, 600),
                'video' => UploadedFile::fake()->create('demo.mp4', 2048, 'video/mp4'),
            ])
            ->assertRedirect();

        $portfolio = PortfolioEloquentModel::query()->where('title', 'Acme Rebrand')->firstOrFail();

        $this->assertSame($admin->id, $portfolio->user_id);
        $this->assertSame(['React', 'Next.js', 'PostgreSQL', 'Stripe'], $portfolio->tech_stack);
        $this->assertNotNull($portfolio->cover_path);
        $this->assertNotNull($portfolio->video_path);
        Storage::disk('r2')->assertExists($portfolio->cover_path);
        Storage::disk('r2')->assertExists($portfolio->video_path);
    }

    public function test_create_without_media_is_allowed(): void
    {
        $this->actingAs($this->superAdmin())
            ->post('/portfolios', [
                'title' => 'No Media',
                'client_name' => 'Client',
                'project_type' => 'web',
            ])
            ->assertRedirect();

        $portfolio = PortfolioEloquentModel::query()->where('title', 'No Media')->firstOrFail();

        $this->assertNull($portfolio->cover_path);
        $this->assertNull($portfolio->video_path);
        $this->assertSame([], $portfolio->tech_stack ?? []);
    }

    public function test_update_persists_tech_stack(): void
    {
        $admin = $this->superAdmin();
        $portfolio = PortfolioEloquentModel::factory()->create([
            'user_id' => $admin->id,
            'tech_stack' => ['Vue'],
        ]);

        $this->actingAs($admin)->put("/portfolios/{$portfolio->uuid}", [
            'title' => $portfolio->title,
            'client_name' => $portfolio->client_name,
            'project_type' => $portfolio->project_type,
            'tech_stack' => ['React', 'Laravel', 'PostgreSQL'],
            'is_public' => true,
        ])->assertRedirect();

        $this->assertSame(
            ['React', 'Laravel', 'PostgreSQL'],
            $portfolio->refresh()->tech_stack,
        );
    }

    public function test_update_replaces_the_cover_and_deletes_the_previous_object(): void
    {
        Storage::fake('r2');
        $admin = $this->superAdmin();

        $this->actingAs($admin)->post('/portfolios', [
            'title' => 'Cloud Platform',
            'client_name' => 'Client',
            'project_type' => 'web',
            'cover' => UploadedFile::fake()->image('old.png', 400, 300),
        ])->assertRedirect();

        $portfolio = PortfolioEloquentModel::query()->where('title', 'Cloud Platform')->firstOrFail();
        $oldPath = $portfolio->cover_path;

        $this->actingAs($admin)->put("/portfolios/{$portfolio->uuid}", [
            'title' => 'Cloud Platform',
            'client_name' => 'Client',
            'project_type' => 'web',
            'cover' => UploadedFile::fake()->image('new.png', 400, 300),
        ])->assertRedirect();

        $newPath = $portfolio->refresh()->cover_path;

        $this->assertNotSame($oldPath, $newPath);
        Storage::disk('r2')->assertMissing($oldPath);
        Storage::disk('r2')->assertExists($newPath);
    }

    public function test_removing_the_cover_without_replacing_clears_it_and_deletes_the_r2_object(): void
    {
        Storage::fake('r2');
        $admin = $this->superAdmin();

        $this->actingAs($admin)->post('/portfolios', [
            'title' => 'Rebrand',
            'client_name' => 'Client',
            'project_type' => 'branding',
            'cover' => UploadedFile::fake()->image('cover.png', 400, 300),
        ])->assertRedirect();

        $portfolio = PortfolioEloquentModel::query()->where('title', 'Rebrand')->firstOrFail();
        $coverPath = $portfolio->cover_path;

        $this->actingAs($admin)->put("/portfolios/{$portfolio->uuid}", [
            'title' => 'Rebrand',
            'client_name' => 'Client',
            'project_type' => 'branding',
            'remove_cover' => true,
        ])->assertRedirect();

        $this->assertNull($portfolio->refresh()->cover_path);
        Storage::disk('r2')->assertMissing($coverPath);
    }

    public function test_uploading_a_new_cover_wins_over_a_remove_flag_sent_in_the_same_request(): void
    {
        Storage::fake('r2');
        $admin = $this->superAdmin();

        $this->actingAs($admin)->post('/portfolios', [
            'title' => 'Conflict Case',
            'client_name' => 'Client',
            'project_type' => 'web',
            'cover' => UploadedFile::fake()->image('old.png', 400, 300),
        ])->assertRedirect();

        $portfolio = PortfolioEloquentModel::query()->where('title', 'Conflict Case')->firstOrFail();

        $this->actingAs($admin)->put("/portfolios/{$portfolio->uuid}", [
            'title' => 'Conflict Case',
            'client_name' => 'Client',
            'project_type' => 'web',
            'remove_cover' => true,
            'cover' => UploadedFile::fake()->image('new.png', 400, 300),
        ])->assertRedirect();

        $this->assertNotNull($portfolio->refresh()->cover_path);
        Storage::disk('r2')->assertExists($portfolio->cover_path);
    }

    public function test_delete_then_restore_a_portfolio(): void
    {
        $admin = $this->superAdmin();
        $portfolio = PortfolioEloquentModel::factory()->create();

        $this->actingAs($admin)->delete("/portfolios/{$portfolio->uuid}")->assertRedirect();
        $this->assertSoftDeleted('portfolios', ['uuid' => $portfolio->uuid]);

        $this->actingAs($admin)->post("/portfolios/{$portfolio->uuid}/restore")->assertRedirect();
        $this->assertDatabaseHas('portfolios', ['uuid' => $portfolio->uuid, 'deleted_at' => null]);
    }

    public function test_bulk_delete_then_restore(): void
    {
        $admin = $this->superAdmin();
        $uuids = PortfolioEloquentModel::factory()->count(3)->create()->pluck('uuid')->all();

        $this->actingAs($admin)->post('/portfolios/bulk-delete', ['uuids' => $uuids])->assertRedirect();
        foreach ($uuids as $uuid) {
            $this->assertSoftDeleted('portfolios', ['uuid' => $uuid]);
        }

        $this->actingAs($admin)->post('/portfolios/bulk-restore', ['uuids' => $uuids])->assertRedirect();
        foreach ($uuids as $uuid) {
            $this->assertDatabaseHas('portfolios', ['uuid' => $uuid, 'deleted_at' => null]);
        }
    }

    public function test_bulk_delete_rejects_more_than_500_uuids(): void
    {
        $uuids = array_map(static fn (): string => (string) Str::uuid(), range(1, 501));

        $this->actingAs($this->superAdmin())
            ->postJson('/portfolios/bulk-delete', ['uuids' => $uuids])
            ->assertStatus(422)
            ->assertJsonValidationErrors('uuids');
    }

    public function test_search_filter_narrows_the_list(): void
    {
        PortfolioEloquentModel::factory()->create(['title' => 'Artificial Intelligence Platform']);
        PortfolioEloquentModel::factory()->create(['title' => 'Gardening App']);

        $this->actingAs($this->superAdmin())
            ->getJson('/portfolios?search=Intelligence')
            ->assertOk()
            ->assertJsonFragment(['title' => 'Artificial Intelligence Platform'])
            ->assertJsonMissing(['title' => 'Gardening App']);
    }

    public function test_a_user_without_permission_cannot_manage_portfolios(): void
    {
        $plain = User::factory()->create();
        $plain->assignRole('USER');

        $this->actingAs($plain)->get('/portfolios')->assertForbidden();
        $this->actingAs($plain)->post('/portfolios', ['title' => 'X'])->assertForbidden();
    }
}
