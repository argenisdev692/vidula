<?php

declare(strict_types=1);

namespace Modules\Portfolio\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Portfolio\Infrastructure\Persistence\Eloquent\Models\PortfolioEloquentModel;
use Shared\Domain\Ports\StoragePort;
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

    /** Put a fake object under the expected portfolios/{kind}/{uuid}/name prefix. */
    private function seedMediaKey(string $kind, string $filename = 'asset.bin'): string
    {
        $prefix = $kind === 'cover'
            ? (string) config('portfolio.cover_prefix', 'portfolios/cover')
            : (string) config('portfolio.video_prefix', 'portfolios/video');
        $key = rtrim($prefix, '/').'/'.Str::uuid()->toString().'/'.$filename;
        Storage::disk('r2')->put($key, 'fake-bytes');

        return $key;
    }

    public function test_super_admin_creates_a_portfolio_with_cover_and_video(): void
    {
        Storage::fake('r2');
        $admin = $this->superAdmin();
        $coverPath = $this->seedMediaKey('cover', 'cover.png');
        $videoPath = $this->seedMediaKey('video', 'demo.mp4');

        $this->actingAs($admin)
            ->post('/portfolios', [
                'title' => 'Acme Rebrand',
                'client_name' => 'Acme Corp',
                'project_type' => 'branding',
                'tech_stack' => ['React', 'Next.js', 'PostgreSQL', 'Stripe'],
                'live_url' => 'https://acme.example.com',
                'is_public' => true,
                'cover_path' => $coverPath,
                'video_path' => $videoPath,
            ])
            ->assertRedirect();

        $portfolio = PortfolioEloquentModel::query()->where('title', 'Acme Rebrand')->firstOrFail();

        $this->assertSame($admin->id, $portfolio->user_id);
        $this->assertSame(['React', 'Next.js', 'PostgreSQL', 'Stripe'], $portfolio->tech_stack);
        $this->assertSame($coverPath, $portfolio->cover_path);
        $this->assertSame($videoPath, $portfolio->video_path);
        Storage::disk('r2')->assertExists($portfolio->cover_path);
        Storage::disk('r2')->assertExists($portfolio->video_path);
    }

    public function test_create_rejects_missing_r2_object_for_cover_path(): void
    {
        Storage::fake('r2');
        $missing = 'portfolios/cover/'.Str::uuid()->toString().'/missing.png';

        $this->actingAs($this->superAdmin())
            ->post('/portfolios', [
                'title' => 'Missing Object',
                'client_name' => 'Client',
                'project_type' => 'web',
                'cover_path' => $missing,
            ])
            ->assertSessionHasErrors('cover_path');

        $this->assertDatabaseMissing('portfolios', ['title' => 'Missing Object']);
    }

    public function test_create_rejects_path_outside_allowed_prefix(): void
    {
        Storage::fake('r2');
        $evil = 'video-exports/_parts/'.Str::uuid()->toString().'/evil.mp4';
        Storage::disk('r2')->put($evil, 'x');

        $this->actingAs($this->superAdmin())
            ->post('/portfolios', [
                'title' => 'Evil Key',
                'client_name' => 'Client',
                'project_type' => 'web',
                'cover_path' => $evil,
            ])
            ->assertSessionHasErrors('cover_path');
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
        $oldPath = $this->seedMediaKey('cover', 'old.png');
        $newPath = $this->seedMediaKey('cover', 'new.png');

        $this->actingAs($admin)->post('/portfolios', [
            'title' => 'Cloud Platform',
            'client_name' => 'Client',
            'project_type' => 'web',
            'cover_path' => $oldPath,
        ])->assertRedirect();

        $portfolio = PortfolioEloquentModel::query()->where('title', 'Cloud Platform')->firstOrFail();
        $this->assertSame($oldPath, $portfolio->cover_path);

        $this->actingAs($admin)->put("/portfolios/{$portfolio->uuid}", [
            'title' => 'Cloud Platform',
            'client_name' => 'Client',
            'project_type' => 'web',
            'cover_path' => $newPath,
        ])->assertRedirect();

        $this->assertSame($newPath, $portfolio->refresh()->cover_path);
        Storage::disk('r2')->assertMissing($oldPath);
        Storage::disk('r2')->assertExists($newPath);
    }

    public function test_removing_the_cover_without_replacing_clears_it_and_deletes_the_r2_object(): void
    {
        Storage::fake('r2');
        $admin = $this->superAdmin();
        $coverPath = $this->seedMediaKey('cover', 'cover.png');

        $this->actingAs($admin)->post('/portfolios', [
            'title' => 'Rebrand',
            'client_name' => 'Client',
            'project_type' => 'branding',
            'cover_path' => $coverPath,
        ])->assertRedirect();

        $portfolio = PortfolioEloquentModel::query()->where('title', 'Rebrand')->firstOrFail();

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
        $oldPath = $this->seedMediaKey('cover', 'old.png');
        $newPath = $this->seedMediaKey('cover', 'new.png');

        $this->actingAs($admin)->post('/portfolios', [
            'title' => 'Conflict Case',
            'client_name' => 'Client',
            'project_type' => 'web',
            'cover_path' => $oldPath,
        ])->assertRedirect();

        $portfolio = PortfolioEloquentModel::query()->where('title', 'Conflict Case')->firstOrFail();

        $this->actingAs($admin)->put("/portfolios/{$portfolio->uuid}", [
            'title' => 'Conflict Case',
            'client_name' => 'Client',
            'project_type' => 'web',
            'remove_cover' => true,
            'cover_path' => $newPath,
        ])->assertRedirect();

        $this->assertSame($newPath, $portfolio->refresh()->cover_path);
        Storage::disk('r2')->assertExists($portfolio->cover_path);
        Storage::disk('r2')->assertMissing($oldPath);
    }

    public function test_presign_returns_upload_url_for_cover(): void
    {
        $this->mock(StoragePort::class, function ($mock): void {
            $mock->shouldReceive('temporaryUploadUrl')
                ->once()
                ->andReturn([
                    'upload_url' => 'https://r2.example.test/put',
                    'headers' => ['Content-Type' => 'image/png'],
                ]);
        });

        $this->actingAs($this->superAdmin())
            ->postJson('/portfolios/uploads/presign', [
                'kind' => 'cover',
                'filename' => 'cover.png',
                'content_type' => 'image/png',
                'size_bytes' => 1024,
            ])
            ->assertOk()
            ->assertJsonPath('data.upload_url', 'https://r2.example.test/put')
            ->assertJsonStructure(['data' => ['upload_url', 'key', 'headers', 'expires_in_seconds']]);
    }

    public function test_presign_rejects_video_mime_for_cover_kind(): void
    {
        $this->actingAs($this->superAdmin())
            ->postJson('/portfolios/uploads/presign', [
                'kind' => 'cover',
                'filename' => 'clip.mp4',
                'content_type' => 'video/mp4',
                'size_bytes' => 1024,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('content_type');
    }

    public function test_users_without_permission_cannot_presign(): void
    {
        $plain = User::factory()->create();
        $plain->assignRole('USER');

        $this->actingAs($plain)
            ->postJson('/portfolios/uploads/presign', [
                'kind' => 'video',
                'filename' => 'demo.mp4',
                'content_type' => 'video/mp4',
                'size_bytes' => 1024,
            ])
            ->assertForbidden();
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

    public function test_export_csv_streams_successfully(): void
    {
        PortfolioEloquentModel::factory()->create(['title' => 'Exportable Portfolio']);

        $this->actingAs($this->superAdmin())
            ->get('/portfolios/export?format=csv')
            ->assertOk();
    }

    public function test_export_xlsx_streams_successfully(): void
    {
        PortfolioEloquentModel::factory()->create(['title' => 'Xlsx Portfolio']);

        $this->actingAs($this->superAdmin())
            ->get('/portfolios/export?format=xlsx')
            ->assertOk();
    }

    public function test_export_pdf_renders_successfully(): void
    {
        PortfolioEloquentModel::factory()->create(['title' => 'Pdf Portfolio']);

        $this->actingAs($this->superAdmin())
            ->get('/portfolios/export?format=pdf')
            ->assertOk();
    }

    public function test_user_without_export_permission_cannot_export(): void
    {
        $plain = User::factory()->create();
        $plain->assignRole('USER');

        $this->actingAs($plain)
            ->get('/portfolios/export?format=csv')
            ->assertForbidden();
    }
}
