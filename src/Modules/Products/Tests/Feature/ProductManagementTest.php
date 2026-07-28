<?php

declare(strict_types=1);

namespace Modules\Products\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\Products\Domain\Enums\GenerationStatus;
use Modules\Products\Domain\Enums\ProductStatus;
use Modules\Products\Domain\Enums\ProductType;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ContentGenerationEloquentModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductEloquentModel;
use Modules\Products\Infrastructure\Queue\GenerateProductContentJob;
use Tests\TestCase;

final class ProductManagementTest extends TestCase
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
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return [
            'type' => 'classroom',
            'title' => 'GitHub Copilot Classroom',
            'description' => 'A classroom product',
            'price' => 1200,
            'currency' => 'EUR',
            'status' => 'draft',
            'level' => 'beginner',
            'language' => 'es',
            'modality' => 'online',
            'total_sessions' => 8,
            'total_hours' => 24,
            'classroom' => [
                'max_students' => 20,
                'objectives' => 'Learn Copilot',
            ],
            ...$overrides,
        ];
    }

    public function test_super_admin_creates_classroom_product(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->post('/products', $this->validPayload())
            ->assertRedirect();

        $product = ProductEloquentModel::query()->where('title', 'GitHub Copilot Classroom')->firstOrFail();

        $this->assertSame($admin->id, $product->user_id);
        $this->assertSame(ProductType::Classroom, $product->type);
        $this->assertSame(ProductStatus::Draft, $product->status);
        $this->assertNotNull($product->classroom);
        $this->assertSame(20, $product->classroom->max_students);
    }

    public function test_update_changes_title_and_status(): void
    {
        $admin = $this->superAdmin();
        $product = ProductEloquentModel::factory()->classroom()->create([
            'title' => 'Old Title',
            'status' => ProductStatus::Draft,
            'user_id' => $admin->id,
        ]);

        $this->actingAs($admin)->put("/products/{$product->uuid}", $this->validPayload([
            'title' => 'New Title',
            'status' => 'published',
        ]))->assertRedirect();

        $product->refresh();
        $this->assertSame('New Title', $product->title);
        $this->assertSame(ProductStatus::Published, $product->status);
    }

    public function test_delete_then_restore(): void
    {
        $admin = $this->superAdmin();
        $product = ProductEloquentModel::factory()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)->delete("/products/{$product->uuid}")->assertRedirect();
        $this->assertSoftDeleted('products', ['uuid' => $product->uuid]);

        $this->actingAs($admin)->post("/products/{$product->uuid}/restore")->assertRedirect();
        $this->assertDatabaseHas('products', ['uuid' => $product->uuid, 'deleted_at' => null]);
    }

    public function test_bulk_delete_then_restore(): void
    {
        $admin = $this->superAdmin();
        $uuids = ProductEloquentModel::factory()->count(3)->create(['user_id' => $admin->id])->pluck('uuid')->all();

        $this->actingAs($admin)->post('/products/bulk-delete', ['uuids' => $uuids])->assertRedirect();
        foreach ($uuids as $uuid) {
            $this->assertSoftDeleted('products', ['uuid' => $uuid]);
        }

        $this->actingAs($admin)->post('/products/bulk-restore', ['uuids' => $uuids])->assertRedirect();
        foreach ($uuids as $uuid) {
            $this->assertDatabaseHas('products', ['uuid' => $uuid, 'deleted_at' => null]);
        }
    }

    public function test_guest_cannot_list_products(): void
    {
        $this->get('/products')->assertRedirect();
    }

    public function test_generate_content_queues_job(): void
    {
        Queue::fake();
        $admin = $this->superAdmin();
        $product = ProductEloquentModel::factory()->classroom()->create(['user_id' => $admin->id]);

        $markdown = <<<'MD'
### Sesión 1 | Intro
- **Tema 1:** First topic
MD;

        $this->actingAs($admin)
            ->post("/products/{$product->uuid}/generate-content", [
                'markdown' => $markdown,
                'mode' => 'replace',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('content_generations', [
            'product_id' => $product->id,
            'status' => GenerationStatus::Pending->value,
        ]);

        Queue::assertPushed(GenerateProductContentJob::class);
    }

    public function test_concurrent_generation_returns_conflict(): void
    {
        Queue::fake();
        $admin = $this->superAdmin();
        $product = ProductEloquentModel::factory()->classroom()->create(['user_id' => $admin->id]);

        ContentGenerationEloquentModel::factory()->inFlight()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->postJson("/products/{$product->uuid}/generate-content", [
                'markdown' => "### Sesión 1 | Intro\n- **Tema 1:** Topic\n",
            ])
            ->assertStatus(409);
    }

    public function test_empty_markdown_is_rejected(): void
    {
        $admin = $this->superAdmin();
        $product = ProductEloquentModel::factory()->classroom()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)
            ->post("/products/{$product->uuid}/generate-content", ['markdown' => '   '])
            ->assertSessionHasErrors('markdown');
    }

    public function test_show_page_includes_generation_and_sessions_props(): void
    {
        $admin = $this->superAdmin();
        $product = ProductEloquentModel::factory()->classroom()->create(['user_id' => $admin->id]);

        ContentGenerationEloquentModel::factory()->completed()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'zip_path' => 'products/'.$product->uuid.'/packages/demo.zip',
        ]);

        $this->actingAs($admin)
            ->get("/products/{$product->uuid}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('products/Show')
                ->has('product')
                ->has('generation')
                ->where('generation.has_package', true)
                ->has('sessions'));
    }

    public function test_package_download_is_not_found_when_incomplete(): void
    {
        $admin = $this->superAdmin();
        $product = ProductEloquentModel::factory()->classroom()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)
            ->getJson("/products/{$product->uuid}/package/download")
            ->assertNotFound();
    }

    public function test_index_includes_client_options_for_form(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->get('/products')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('products/Index')
                ->has('clients')
                ->has('products'));
    }
}
