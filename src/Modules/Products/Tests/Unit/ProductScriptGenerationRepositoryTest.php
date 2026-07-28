<?php

declare(strict_types=1);

namespace Modules\Products\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Products\Application\DTOs\GeneratedTopicContentData;
use Modules\Products\Application\DTOs\SeedOutlineData;
use Modules\Products\Application\DTOs\SeedSessionData;
use Modules\Products\Application\DTOs\SeedTopicData;
use Modules\Products\Domain\Enums\ScriptStatus;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductEloquentModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductScriptEloquentModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductSessionEloquentModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductSessionTopicEloquentModel;
use Modules\Products\Infrastructure\Persistence\Repositories\EloquentContentGenerationRepository;
use Modules\Products\Infrastructure\Persistence\Repositories\EloquentProductScriptRepository;
use Tests\TestCase;

/**
 * T020 / T028 — sources_json persistence, needs_review, preserve vs force-replace.
 */
final class ProductScriptGenerationRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentProductScriptRepository $scripts;

    private EloquentContentGenerationRepository $generations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scripts = new EloquentProductScriptRepository;
        $this->generations = new EloquentContentGenerationRepository;
    }

    public function test_save_generated_content_stores_sources_json_on_topic_and_script(): void
    {
        [$topic] = $this->seedTopicTree('Intro to Copilot');

        $sources = [
            [
                'type' => 'web',
                'title' => 'Copilot docs',
                'url' => 'https://example.test/copilot',
                'snippet' => 'GitHub Copilot overview',
            ],
        ];

        $this->scripts->saveGeneratedContent($topic->uuid, new GeneratedTopicContentData(
            intro: 'Hello',
            body: 'Body',
            outro: 'Bye',
            notes: 'Notes',
            estimatedMinutes: 12,
            keyPoints: ['A'],
            sources: $sources,
            status: ScriptStatus::Generated,
            model: 'test-model',
        ));

        $topic->refresh();
        $script = $topic->script;
        $this->assertNotNull($script);
        $this->assertSame($sources, $topic->sources_json);
        $this->assertSame($sources, $script->sources_json);
        $this->assertSame(ScriptStatus::Generated, $script->status);
    }

    public function test_save_generated_content_preserves_verified_unless_force_replace(): void
    {
        [$topic, $script] = $this->seedTopicTree('Verified Topic');
        $script->update([
            'body' => 'Human approved body',
            'status' => ScriptStatus::Verified,
        ]);

        $this->scripts->saveGeneratedContent($topic->uuid, new GeneratedTopicContentData(
            intro: null,
            body: 'AI overwrite',
            outro: null,
            notes: null,
            estimatedMinutes: 5,
            keyPoints: [],
            sources: [['type' => 'web', 'title' => 'x', 'url' => 'https://x.test', 'snippet' => 'y']],
            status: ScriptStatus::Generated,
            model: 'test-model',
        ));

        $script->refresh();
        $this->assertSame('Human approved body', $script->body);
        $this->assertSame(ScriptStatus::Verified, $script->status);

        $this->scripts->saveGeneratedContent($topic->uuid, new GeneratedTopicContentData(
            intro: null,
            body: 'Forced AI body',
            outro: null,
            notes: 'forced',
            estimatedMinutes: 8,
            keyPoints: [],
            sources: [['type' => 'web', 'title' => 'x', 'url' => 'https://x.test', 'snippet' => 'y']],
            status: ScriptStatus::Generated,
            model: 'test-model',
        ), forceReplace: true);

        $script->refresh();
        $this->assertSame('Forced AI body', $script->body);
        $this->assertSame(ScriptStatus::Generated, $script->status);
    }

    public function test_mark_needs_review_appends_reason(): void
    {
        [$topic, $script] = $this->seedTopicTree('Needs review topic');
        $script->update(['status' => ScriptStatus::Draft, 'notes' => 'Base']);

        $this->scripts->markNeedsReview($topic->uuid, 'Research empty');

        $script->refresh();
        $this->assertSame(ScriptStatus::NeedsReview, $script->status);
        $this->assertStringContainsString('[needs_review] Research empty', (string) $script->notes);
    }

    public function test_replace_content_tree_preserves_verified_titles_unless_forced(): void
    {
        $product = ProductEloquentModel::factory()->classroom()->create();
        $session = ProductSessionEloquentModel::factory()->number(1)->create([
            'product_id' => $product->id,
            'title' => 'Session 1',
        ]);
        $topic = ProductSessionTopicEloquentModel::factory()->create([
            'product_session_id' => $session->id,
            'title' => 'Keep Me',
            'sort_order' => 1,
        ]);
        ProductScriptEloquentModel::factory()->verified()->create([
            'product_session_topic_id' => $topic->id,
            'body' => 'Keep this body',
        ]);

        $outline = new SeedOutlineData([
            new SeedSessionData(1, 'Session 1', [
                new SeedTopicData('Keep Me', 1),
                new SeedTopicData('New Topic', 2),
            ]),
        ]);

        $this->generations->replaceContentTree($product->uuid, $outline, forceReplace: false);

        $kept = ProductSessionTopicEloquentModel::query()
            ->whereHas('session', fn ($q) => $q->where('product_id', $product->id))
            ->where('title', 'Keep Me')
            ->with('script')
            ->firstOrFail();

        $this->assertSame('Keep this body', $kept->script?->body);
        $this->assertSame(ScriptStatus::Verified, $kept->script?->status);

        $this->generations->replaceContentTree($product->uuid, $outline, forceReplace: true);

        $forced = ProductSessionTopicEloquentModel::query()
            ->whereHas('session', fn ($q) => $q->where('product_id', $product->id))
            ->where('title', 'Keep Me')
            ->with('script')
            ->firstOrFail();

        $this->assertSame(ScriptStatus::Draft, $forced->script?->status);
        $this->assertNull($forced->script?->body);
    }

    /**
     * @return array{0: ProductSessionTopicEloquentModel, 1: ProductScriptEloquentModel}
     */
    private function seedTopicTree(string $title): array
    {
        $product = ProductEloquentModel::factory()->classroom()->create();
        $session = ProductSessionEloquentModel::factory()->number(1)->create([
            'product_id' => $product->id,
        ]);
        $topic = ProductSessionTopicEloquentModel::factory()->create([
            'product_session_id' => $session->id,
            'title' => $title,
            'sort_order' => 1,
        ]);
        $script = ProductScriptEloquentModel::factory()->create([
            'product_session_topic_id' => $topic->id,
            'status' => ScriptStatus::Draft,
        ]);

        return [$topic, $script];
    }
}
