<?php

declare(strict_types=1);

namespace Modules\Products\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Modules\Products\Application\DTOs\ConsistencyReportData;
use Modules\Products\Application\DTOs\GeneratedTopicContentData;
use Modules\Products\Application\Services\SeedOutlineParser;
use Modules\Products\Domain\Enums\ScriptStatus;
use Modules\Products\Domain\Ports\ContentGenerationRepositoryPort;
use Modules\Products\Domain\Ports\CourseRendererPort;
use Modules\Products\Domain\Ports\ProductContentGeneratorPort;
use Modules\Products\Domain\Ports\ProductMaterialRepositoryPort;
use Modules\Products\Domain\Ports\ProductScriptRepositoryPort;
use Modules\Products\Domain\Ports\ZipPackagePort;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductEloquentModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductMaterialEloquentModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductScriptEloquentModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductSessionEloquentModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductSessionTopicEloquentModel;
use Modules\Products\Infrastructure\Queue\GenerateProductContentJob;
use Shared\Domain\Ports\AuditPort;
use Shared\Domain\Ports\StoragePort;
use Tests\TestCase;

/**
 * T020 / T023 / T028 — generation job with faked AI port, materials, scripts.
 */
final class ProductContentPipelineTest extends TestCase
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

    public function test_generation_job_persists_sources_and_completes_with_fake_ai(): void
    {
        $admin = $this->superAdmin();
        $product = ProductEloquentModel::factory()->classroom()->create([
            'user_id' => $admin->id,
            'title' => 'Copilot Course',
        ]);

        $markdown = <<<'MD'
### Sesión 1 | Intro
- **Tema 1:** First topic
MD;

        $generation = $product->contentGenerations()->create([
            'user_id' => $admin->id,
            'status' => 'pending',
            'mode' => 'replace',
            'source_markdown' => $markdown,
            'progress' => 0,
        ]);

        $sources = [[
            'type' => 'documentation',
            'title' => 'Laravel — Eloquent',
            'url' => 'https://laravel.com/docs/eloquent',
            'snippet' => 'Eloquent ORM',
        ]];

        $this->instance(
            ProductContentGeneratorPort::class,
            Mockery::mock(ProductContentGeneratorPort::class, function ($mock) use ($sources): void {
                $mock->shouldReceive('generateTopic')
                    ->once()
                    ->andReturn(new GeneratedTopicContentData(
                        intro: null,
                        body: 'Lesson body',
                        outro: null,
                        notes: 'Presenter notes',
                        estimatedMinutes: 15,
                        keyPoints: ['Eloquent'],
                        sources: $sources,
                        status: ScriptStatus::Generated,
                        model: 'fake-model',
                    ));

                $mock->shouldReceive('verifyConsistency')
                    ->once()
                    ->andReturn(new ConsistencyReportData(
                        consistent: true,
                        coverageScore: 100,
                        missingTitles: [],
                        driftedTopics: [],
                        summary: 'OK',
                    ));
            }),
        );

        $this->fakeStorage();

        (new GenerateProductContentJob($generation->uuid, $admin->id))->handle(
            app(ContentGenerationRepositoryPort::class),
            app(ProductContentGeneratorPort::class),
            app(ProductScriptRepositoryPort::class),
            app(ProductMaterialRepositoryPort::class),
            app(CourseRendererPort::class),
            app(ZipPackagePort::class),
            app(StoragePort::class),
            app(SeedOutlineParser::class),
            app(AuditPort::class),
        );

        $generation->refresh();
        $this->assertSame('completed', $generation->status instanceof \BackedEnum
            ? $generation->status->value
            : (string) $generation->status);
        $this->assertNotNull($generation->md_path);
        $this->assertNotNull($generation->pdf_path);
        $this->assertNotNull($generation->zip_path);

        $topic = ProductSessionTopicEloquentModel::query()
            ->whereHas('session', fn ($q) => $q->where('product_id', $product->id))
            ->with('script')
            ->firstOrFail();

        $this->assertSame('First topic', $topic->title);
        $this->assertSame($sources, $topic->sources_json);
        $this->assertSame($sources, $topic->script?->sources_json);
        $this->assertSame(ScriptStatus::Generated, $topic->script?->status);
    }

    public function test_generation_job_marks_needs_review_when_ai_throws(): void
    {
        $admin = $this->superAdmin();
        $product = ProductEloquentModel::factory()->classroom()->create(['user_id' => $admin->id]);

        $generation = $product->contentGenerations()->create([
            'user_id' => $admin->id,
            'status' => 'pending',
            'mode' => 'replace',
            'source_markdown' => "### Sesión 1 | Intro\n- **Tema 1:** Broken topic\n",
            'progress' => 0,
        ]);

        $this->instance(
            ProductContentGeneratorPort::class,
            Mockery::mock(ProductContentGeneratorPort::class, function ($mock): void {
                $mock->shouldReceive('generateTopic')
                    ->once()
                    ->andThrow(new \RuntimeException('Research provider down'));

                $mock->shouldReceive('verifyConsistency')
                    ->once()
                    ->andReturn(new ConsistencyReportData(
                        consistent: true,
                        coverageScore: 100,
                        missingTitles: [],
                        driftedTopics: [],
                        summary: 'OK',
                    ));
            }),
        );

        $this->fakeStorage();

        (new GenerateProductContentJob($generation->uuid, $admin->id))->handle(
            app(ContentGenerationRepositoryPort::class),
            app(ProductContentGeneratorPort::class),
            app(ProductScriptRepositoryPort::class),
            app(ProductMaterialRepositoryPort::class),
            app(CourseRendererPort::class),
            app(ZipPackagePort::class),
            app(StoragePort::class),
            app(SeedOutlineParser::class),
            app(AuditPort::class),
        );

        $script = ProductScriptEloquentModel::query()
            ->whereHas('topic.session', fn ($q) => $q->where('product_id', $product->id))
            ->firstOrFail();

        $this->assertSame(ScriptStatus::NeedsReview, $script->status);
        $this->assertStringContainsString('Research provider down', (string) $script->notes);
        $generation->refresh();
        $this->assertSame('completed', $generation->status instanceof \BackedEnum
            ? $generation->status->value
            : (string) $generation->status);
    }

    public function test_script_update_sets_verified_status(): void
    {
        $admin = $this->superAdmin();
        $product = ProductEloquentModel::factory()->classroom()->create(['user_id' => $admin->id]);
        $session = ProductSessionEloquentModel::factory()->number(1)->create(['product_id' => $product->id]);
        $topic = ProductSessionTopicEloquentModel::factory()->create([
            'product_session_id' => $session->id,
            'title' => 'Editable topic',
        ]);
        ProductScriptEloquentModel::factory()->generated()->create([
            'product_session_topic_id' => $topic->id,
            'body' => 'Old body',
        ]);

        $this->actingAs($admin)
            ->putJson("/products/{$product->uuid}/topics/{$topic->uuid}/script", [
                'body' => 'Reviewed body',
                'status' => 'verified',
            ])
            ->assertOk()
            ->assertJsonPath('data.body', 'Reviewed body')
            ->assertJsonPath('data.status', 'verified');
    }

    public function test_script_from_other_product_is_not_found(): void
    {
        $admin = $this->superAdmin();
        $productA = ProductEloquentModel::factory()->classroom()->create(['user_id' => $admin->id]);
        $productB = ProductEloquentModel::factory()->classroom()->create(['user_id' => $admin->id]);
        $session = ProductSessionEloquentModel::factory()->create(['product_id' => $productB->id]);
        $topic = ProductSessionTopicEloquentModel::factory()->create(['product_session_id' => $session->id]);
        ProductScriptEloquentModel::factory()->create(['product_session_topic_id' => $topic->id]);

        $this->actingAs($admin)
            ->getJson("/products/{$productA->uuid}/topics/{$topic->uuid}/script")
            ->assertNotFound();
    }

    public function test_material_download_returns_signed_url(): void
    {
        $admin = $this->superAdmin();
        $product = ProductEloquentModel::factory()->classroom()->create(['user_id' => $admin->id]);
        $material = ProductMaterialEloquentModel::factory()->create([
            'product_id' => $product->id,
            'path' => 'products/demo/course.md',
        ]);

        $this->fakeStorage();

        $this->actingAs($admin)
            ->getJson("/products/{$product->uuid}/materials/{$material->uuid}/download")
            ->assertOk()
            ->assertJsonPath('data.url', 'https://signed.example/products/demo/course.md');
    }

    public function test_material_replace_rejects_video_upload(): void
    {
        $admin = $this->superAdmin();
        $product = ProductEloquentModel::factory()->classroom()->create(['user_id' => $admin->id]);
        $material = ProductMaterialEloquentModel::factory()->create([
            'product_id' => $product->id,
            'path' => 'products/demo/course.md',
        ]);

        $this->fakeStorage();

        $this->actingAs($admin)
            ->post("/products/{$product->uuid}/materials/{$material->uuid}/replace", [
                'file' => UploadedFile::fake()->create('clip.mp4', 1024, 'video/mp4'),
            ])
            ->assertSessionHasErrors('file');
    }

    public function test_material_replace_accepts_markdown(): void
    {
        $admin = $this->superAdmin();
        $product = ProductEloquentModel::factory()->classroom()->create(['user_id' => $admin->id]);
        $material = ProductMaterialEloquentModel::factory()->create([
            'product_id' => $product->id,
            'path' => 'products/demo/course.md',
            'original_name' => 'course.md',
        ]);

        $this->fakeStorage();

        $this->actingAs($admin)
            ->postJson("/products/{$product->uuid}/materials/{$material->uuid}/replace", [
                'file' => UploadedFile::fake()->createWithContent('course.md', "# Updated\n"),
                'title' => 'Course Markdown',
            ])
            ->assertOk();

        $material->refresh();
        $this->assertSame('Course Markdown', $material->title);
        $this->assertNotSame('products/demo/course.md', $material->path);
    }

    public function test_generate_content_rejects_non_markdown_upload(): void
    {
        Queue::fake();
        $admin = $this->superAdmin();
        $product = ProductEloquentModel::factory()->classroom()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)
            ->post("/products/{$product->uuid}/generate-content", [
                'file' => UploadedFile::fake()->create('seed.mp4', 100, 'video/mp4'),
            ])
            ->assertSessionHasErrors('file');

        Queue::assertNothingPushed();
    }

    private function fakeStorage(): void
    {
        $this->instance(StoragePort::class, new class implements StoragePort
        {
            /** @var array<string, string> */
            private array $files = [];

            public function put(string $path, string $contents, string $visibility = 'private'): string
            {
                $this->files[$path] = $contents;

                return $path;
            }

            public function putFile(string $directory, \SplFileInfo $file, string $visibility = 'private'): string
            {
                $path = $directory.'/'.($file->getFilename() ?: 'upload.bin');
                $this->files[$path] = is_file($file->getPathname())
                    ? (string) file_get_contents($file->getPathname())
                    : '';

                return $path;
            }

            public function temporaryUrl(string $path, \DateTimeInterface $expiresAt): string
            {
                return 'https://signed.example/'.$path;
            }

            public function temporaryUploadUrl(string $path, \DateTimeInterface $expiresAt): array
            {
                return ['upload_url' => 'https://upload.example/'.$path, 'headers' => []];
            }

            public function publicUrl(string $path): string
            {
                return 'https://cdn.example/'.$path;
            }

            public function copyToLocal(string $path, string $localPath): void
            {
                file_put_contents($localPath, $this->files[$path] ?? '');
            }

            public function putFromPath(string $path, string $localPath, string $visibility = 'private'): string
            {
                $this->files[$path] = is_file($localPath) ? (string) file_get_contents($localPath) : '';

                return $path;
            }

            public function delete(string $path): bool
            {
                unset($this->files[$path]);

                return true;
            }

            public function exists(string $path): bool
            {
                return array_key_exists($path, $this->files);
            }
        });
    }
}
