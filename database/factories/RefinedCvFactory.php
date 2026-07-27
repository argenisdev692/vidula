<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\AiResumeStudio\Domain\Enums\StudioMode;
use Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models\RefinedCvEloquentModel;
use Modules\Cvs\Infrastructure\Persistence\Eloquent\Models\CvEloquentModel;

/**
 * @extends Factory<RefinedCvEloquentModel>
 */
final class RefinedCvFactory extends Factory
{
    /**
     * @var class-string<RefinedCvEloquentModel>
     */
    protected $model = RefinedCvEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid7(),
            'user_id' => User::factory(),
            'cv_id' => CvEloquentModel::factory(),
            'studio_run_id' => null,
            'mode' => StudioMode::Career->value,
            'target_job_title' => 'Fullstack Developer',
            'resume_language' => 'en',
            'provider' => 'openai',
            'ats_score' => 82,
            'refined_md' => "# Refined CV\n\nATS-optimized content.",
            'feedback' => ['summary' => 'Good keyword density.'],
            'version' => 1,
        ];
    }
}
