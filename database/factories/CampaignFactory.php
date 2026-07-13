<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Campaigns\Domain\Enums\CampaignAdFormat;
use Modules\Campaigns\Domain\Enums\CampaignBrandVoice;
use Modules\Campaigns\Domain\Enums\CampaignBusinessGoal;
use Modules\Campaigns\Domain\Enums\CampaignFunnelStage;
use Modules\Campaigns\Domain\Enums\CampaignLanguage;
use Modules\Campaigns\Domain\Enums\CampaignPlatform;
use Modules\Campaigns\Domain\Enums\CampaignStatus;
use Modules\Campaigns\Infrastructure\Persistence\Eloquent\Models\CampaignEloquentModel;

/**
 * @extends Factory<CampaignEloquentModel>
 */
final class CampaignFactory extends Factory
{
    /**
     * @var class-string<CampaignEloquentModel>
     */
    protected $model = CampaignEloquentModel::class;

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
            'business_goal' => CampaignBusinessGoal::Leads,
            'brand_voice' => CampaignBrandVoice::Professional,
            'funnel_stage' => CampaignFunnelStage::Tofu,
            'platform' => CampaignPlatform::Both,
            'ad_format' => CampaignAdFormat::LeadForm,
            'language' => CampaignLanguage::Spanish,
            'provider' => 'gemini',
            'status' => CampaignStatus::Draft,
            'scheduled_at' => null,
            'published_at' => null,
            'headline' => $topic,
            'primary_text' => $this->faker->paragraphs(2, true),
            'description' => $this->faker->sentence(6),
            'call_to_action' => 'GET_QUOTE',
            'hashtags' => ['#leads', '#metaads'],
            'lead_form_questions' => ['What is your budget range?', 'When are you looking to start?'],
            'targeting_suggestions' => ['Lookalike of existing customers', 'Interest: small business owners'],
            'platforms' => null,
            'cover_image_path' => null,
            'cover_image_prompt' => null,
            'scores' => null,
            'audience_fit_score' => null,
            'virality_score' => null,
            'roi_potential_score' => null,
            'lead_quality_score' => null,
            'trend_relevance_score' => null,
            'overall_score_avg' => null,
            'success_probability_label' => null,
            'all_scores_pass' => false,
            'iterations_required' => null,
            'quality_warning' => false,
            'quality_warning_message' => null,
            'optimization_suggestions' => null,
            'research_sources' => null,
            'tavily_data_used' => null,
            'ai_detection_risk' => null,
            'created_by' => User::factory(),
        ];
    }

    public function ready(): self
    {
        $score = static fn (int $value, int $threshold): array => [
            'value' => $value,
            'threshold' => $threshold,
            'passes' => $value >= $threshold,
            'factors' => ['primary_factor' => $value],
            'explanation' => 'Solid attempt.',
        ];

        return $this->state(fn (): array => [
            'status' => CampaignStatus::Ready,
            'audience_fit_score' => 82,
            'virality_score' => 76,
            'roi_potential_score' => 74,
            'lead_quality_score' => 78,
            'trend_relevance_score' => 79,
            'overall_score_avg' => 78,
            'success_probability_label' => 'high',
            'all_scores_pass' => true,
            'iterations_required' => 2,
            'scores' => [
                'audience_fit_score' => $score(82, 75),
                'virality_score' => $score(76, 70),
                'roi_potential_score' => $score(74, 70),
                'lead_quality_score' => $score(78, 70),
                'trend_relevance_score' => $score(79, 70),
                'all_scores_pass' => true,
                'overall_average' => 78,
                'success_probability_label' => 'high',
            ],
        ]);
    }
}
