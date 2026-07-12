<?php

declare(strict_types=1);

namespace Modules\Post\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Post\Infrastructure\Ai\GeneratePostContentAgent;
use Modules\Post\Infrastructure\Ai\SuggestPostTopicsAgent;
use Tests\TestCase;

/**
 * Tavily is not faked explicitly — TAVILY_API_KEY is unset in the testing
 * environment, so TavilyResearchAdapter::search() short-circuits to an empty
 * array without any HTTP call (see its early-return guard).
 */
final class PostAiAssistTest extends TestCase
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

    public function test_suggest_topics_returns_ten_ideas(): void
    {
        SuggestPostTopicsAgent::fake([
            [
                'niche_analysis' => [
                    'target_audience' => 'SMB owners',
                    'trending_topics' => ['AI automation'],
                ],
                'content_ideas' => array_map(static fn (int $i): array => [
                    'title' => "Idea {$i}",
                    'angle' => 'angle',
                    'hook' => 'hook',
                    'estimated_virality' => 70,
                    'estimated_roi' => 65,
                    'eeat_potential' => 80,
                    'why_it_works' => 'because',
                    'key_trend' => 'trend',
                ], range(1, 10)),
            ],
        ]);

        $this->actingAs($this->superAdmin())
            ->postJson('/posts/ai/suggest-topics', ['provider' => 'openai'])
            ->assertOk()
            ->assertJsonCount(10, 'data');
    }

    public function test_generate_content_returns_a_scored_draft_without_an_image(): void
    {
        GeneratePostContentAgent::fake([
            [
                'title' => 'Generated Title',
                'content' => 'Generated body content.',
                'excerpt' => 'Generated excerpt.',
                'meta_title' => 'Meta title',
                'meta_description' => 'Meta description',
                'meta_keywords' => 'kw1, kw2',
                'cover_image_prompt' => 'A prompt',
                'scores' => [
                    'seo_score' => 80,
                    'eeat_score' => 75,
                    'human_writing_index' => 82,
                    'ai_detection_risk' => 15,
                ],
                'seo_analysis' => [
                    'primary_keyword' => 'onboarding',
                    'lsi_keywords' => ['kw1', 'kw2'],
                ],
                'optimization_suggestions' => ['Add more data.'],
            ],
        ]);

        $this->actingAs($this->superAdmin())
            ->postJson('/posts/ai/generate-content', [
                'topic' => 'Onboarding automation',
                'provider' => 'openai',
                'generate_cover_image' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Generated Title')
            ->assertJsonPath('data.seo_score', 80)
            ->assertJsonPath('data.cover_image_path', null);
    }

    public function test_ai_endpoints_are_gated_by_create_permission(): void
    {
        $plain = User::factory()->create();
        $plain->assignRole('USER');

        $this->actingAs($plain)
            ->postJson('/posts/ai/suggest-topics', ['provider' => 'openai'])
            ->assertForbidden();
    }
}
