<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\SocialMedia\Domain\Enums\BrandVoice;
use Modules\SocialMedia\Domain\Enums\BusinessGoal;
use Modules\SocialMedia\Domain\Enums\ContentLanguage;
use Modules\SocialMedia\Domain\Enums\FunnelStage;
use Modules\SocialMedia\Domain\Enums\SocialMediaContentStatus;
use Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\SocialMediaContentEloquentModel;

/**
 * @extends Factory<SocialMediaContentEloquentModel>
 */
final class SocialMediaContentFactory extends Factory
{
    /**
     * @var class-string<SocialMediaContentEloquentModel>
     */
    protected $model = SocialMediaContentEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $topic = Str::title($this->faker->unique()->sentence(4));

        return [
            'uuid' => (string) Str::uuid7(),
            'niche' => $this->faker->word(),
            'topic' => $topic,
            'angle' => $this->faker->sentence(8),
            'hook' => $this->faker->sentence(6),
            'key_trend' => $this->faker->sentence(4),
            'audience' => $this->faker->jobTitle(),
            'business_goal' => BusinessGoal::Engagement,
            'brand_voice' => BrandVoice::Professional,
            'funnel_stage' => FunnelStage::Tofu,
            'language' => ContentLanguage::Spanish,
            'provider' => 'gemini',
            'status' => SocialMediaContentStatus::Draft,
            'scheduled_at' => null,
            'published_at' => null,
            'headline' => $topic,
            'body' => $this->faker->paragraphs(3, true),
            'call_to_action' => $this->faker->sentence(6),
            'hashtags' => ['#tech', '#laravel'],
            'platforms' => null,
            'cover_image_path' => null,
            'cover_image_prompt' => null,
            'scores' => null,
            'human_writing_index' => null,
            'virality_score' => null,
            'engagement_score' => null,
            'roi_score' => null,
            'trend_alignment' => null,
            'overall_score_avg' => null,
            'all_scores_pass' => false,
            'iterations_required' => null,
            'quality_warning' => false,
            'quality_warning_message' => null,
            'eeat_analysis' => null,
            'optimization_suggestions' => null,
            'research_sources' => null,
            'tavily_data_used' => null,
            'ai_detection_risk' => null,
            'created_by' => User::factory(),
        ];
    }

    public function ready(): self
    {
        return $this->state(fn (): array => [
            'status' => SocialMediaContentStatus::Ready,
            'human_writing_index' => 82,
            'virality_score' => 76,
            'engagement_score' => 78,
            'roi_score' => 74,
            'trend_alignment' => 79,
            'overall_score_avg' => 78,
            'all_scores_pass' => true,
            'iterations_required' => 2,
        ]);
    }
}
