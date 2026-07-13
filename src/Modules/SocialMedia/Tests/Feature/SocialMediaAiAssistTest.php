<?php

declare(strict_types=1);

namespace Modules\SocialMedia\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\SocialMedia\Application\DTOs\GenerateSocialMediaContentData;
use Modules\SocialMedia\Infrastructure\Ai\GenerateSocialMediaContentAgent;
use Modules\SocialMedia\Infrastructure\Ai\SuggestSocialMediaTopicsAgent;
use Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\SocialMediaContentEloquentModel;
use Modules\SocialMedia\Infrastructure\Queue\GenerateSocialMediaContentJob;
use Tests\TestCase;

/**
 * Tavily and ElevenLabs are not faked explicitly — their API keys are unset in
 * the testing environment, so both adapters short-circuit (empty research /
 * null audio) without any HTTP call, same convention as
 * {@see \Modules\Post\Tests\Feature\PostAiAssistTest}.
 */
final class SocialMediaAiAssistTest extends TestCase
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

    public function test_suggest_topics_returns_ten_ideas_classified_by_funnel_stage(): void
    {
        SuggestSocialMediaTopicsAgent::fake([
            [
                'niche_analysis' => [
                    'target_audience' => 'SMB owners',
                    'key_pain_points' => ['slow onboarding'],
                    'trending_topics' => ['AI automation'],
                    'tavily_insights' => [],
                ],
                'viral_topics' => array_map(static fn (int $i): array => [
                    'title' => "Idea {$i}",
                    'angle' => 'angle',
                    'hook' => 'hook',
                    'platform' => 'linkedin',
                    'estimated_virality' => 70,
                    'estimated_engagement' => 'high',
                    'estimated_roi' => 65,
                    'difficulty' => 'easy',
                    'why_it_works' => 'timely news',
                    'key_trend' => 'trend',
                    'suggested_format' => 'post',
                    'content_type' => 'educational',
                    'funnel_stage' => $i <= 6 ? 'tofu' : ($i <= 9 ? 'mofu' : 'bofu'),
                ], range(1, 10)),
            ],
        ]);

        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->postJson('/social-media/ai/suggest-topics', ['provider' => 'openai', 'language' => 'en'])
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('data.9.funnel_stage', 'bofu');

        $this->assertDatabaseHas('activity_log', [
            'event' => 'social_media.ai.topics_suggested',
            'causer_id' => $admin->id,
        ]);
    }

    public function test_generate_content_dispatches_the_quality_loop_job(): void
    {
        Queue::fake();

        $this->actingAs($this->superAdmin())
            ->postJson('/social-media/ai/generate-content', [
                'topic' => 'Laravel 13 release',
                'provider' => 'openai',
                'language' => 'en',
                'business_goal' => 'awareness',
                'brand_voice' => 'professional',
                'funnel_stage' => 'tofu',
                'generate_images' => false,
                'generate_voiceover' => false,
            ])
            ->assertStatus(202)
            ->assertJsonPath('data.status', 'generating');

        $this->assertDatabaseHas('social_media_contents', [
            'topic' => 'Laravel 13 release',
            'status' => 'generating',
        ]);

        Queue::assertPushed(GenerateSocialMediaContentJob::class);
    }

    public function test_quality_loop_job_persists_a_ready_package_on_first_pass(): void
    {
        GenerateSocialMediaContentAgent::fake([$this->passingAttempt()]);

        $content = SocialMediaContentEloquentModel::factory()->create(['status' => 'generating']);

        $job = new GenerateSocialMediaContentJob(
            $content->uuid,
            GenerateSocialMediaContentData::from([
                'topic' => $content->topic,
                'provider' => 'openai',
                'language' => 'en',
                'business_goal' => 'awareness',
                'brand_voice' => 'professional',
                'funnel_stage' => 'tofu',
                'generate_images' => false,
                'generate_voiceover' => false,
            ]),
        );

        app()->call([$job, 'handle']);

        $content->refresh();
        $this->assertSame('ready', $content->status->value);
        $this->assertTrue($content->all_scores_pass);
        $this->assertSame(1, $content->iterations_required);
        $this->assertFalse($content->quality_warning);
        $this->assertSame('Generated headline', $content->headline);
    }

    public function test_ai_endpoints_are_gated_by_create_permission(): void
    {
        $plain = User::factory()->create();
        $plain->assignRole('USER');

        $this->actingAs($plain)
            ->postJson('/social-media/ai/suggest-topics', ['provider' => 'openai', 'language' => 'en'])
            ->assertForbidden();
    }

    /**
     * @return array<string, mixed>
     */
    private function passingAttempt(): array
    {
        $platform = static fn (bool $withScript = false): array => array_filter([
            'adapted_content' => 'Adapted content.',
            'character_count' => 120,
            'is_thread' => false,
            'thread_tweets' => [],
            'hashtags' => ['#tech'],
            'video_script' => $withScript ? 'Hook. Problem. Payoff. Proof. CTA.' : null,
            'image_concept' => ['title' => 'Short Title', 'visual' => 'a glowing node network'],
        ], static fn ($v): bool => $v !== null);

        $score = static fn (int $value, array $factors): array => [
            'value' => $value,
            'factors' => $factors,
            'explanation' => 'Solid attempt.',
        ];

        return [
            'content' => [
                'headline' => 'Generated headline',
                'body' => 'Generated body.',
                'call_to_action' => 'Follow for more.',
                'hashtags' => ['#tech', '#laravel'],
            ],
            'platforms' => [
                'linkedin' => $platform(),
                'twitter' => $platform(),
                'instagram' => $platform(),
                'facebook' => $platform(),
                'tiktok' => $platform(withScript: true),
            ],
            'cover_image_concept' => ['title' => 'Short Title', 'visual' => 'a glowing node network'],
            'scores' => [
                'human_writing_index' => $score(82, ['natural_language' => 85, 'personal_anecdotes' => 78, 'varied_structure' => 83, 'emotional_depth' => 80]),
                'virality_score' => $score(76, ['hook_strength' => 82, 'shareability' => 74, 'timing' => 71, 'emotional_trigger' => 77]),
                'engagement_score' => $score(78, ['cta_strength' => 80, 'interaction_prompt' => 76, 'value_density' => 78, 'emotional_connection' => 77]),
                'roi_score' => $score(74, ['conversion_potential' => 75, 'brand_alignment' => 73, 'lead_generation' => 74]),
                'trend_alignment' => $score(79, ['current_trend' => 81, 'timeliness' => 77, 'platform_format' => 79]),
            ],
            'eeat_analysis' => [
                'experience_signals' => ['first-hand scenario'],
                'expertise_signals' => ['domain terms'],
                'authoritativeness_signals' => ['cites research'],
                'trustworthiness_signals' => ['acknowledges a limitation'],
            ],
            'optimization_suggestions' => ['Add one more data point.'],
            'research_sources' => [],
            'tavily_data_used' => [],
            'ai_detection_risk' => ['value' => 15, 'label' => 'low', 'explanation' => 'Reads as human-written.'],
        ];
    }
}
