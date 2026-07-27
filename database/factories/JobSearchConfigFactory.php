<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\AiResumeStudio\Domain\Enums\JobSearchConfigStatus;
use Modules\AiResumeStudio\Domain\Enums\StudioMode;
use Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models\JobSearchConfigEloquentModel;
use Modules\Cvs\Infrastructure\Persistence\Eloquent\Models\CvEloquentModel;

/**
 * @extends Factory<JobSearchConfigEloquentModel>
 */
final class JobSearchConfigFactory extends Factory
{
    /**
     * @var class-string<JobSearchConfigEloquentModel>
     */
    protected $model = JobSearchConfigEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid7(),
            'user_id' => User::factory(),
            'cv_id' => CvEloquentModel::factory(),
            'mode' => StudioMode::Career->value,
            'keywords' => 'laravel fullstack developer',
            'location_scope' => 'remote',
            'search_language' => 'both',
            'resume_language' => 'en',
            'targeting_prompt' => null,
            'schedule_enabled' => false,
            'deep_extract_enabled' => false,
            'auto_send_enabled' => false,
            'provider' => 'openai',
            'status' => JobSearchConfigStatus::Active->value,
        ];
    }

    public function scheduled(): static
    {
        return $this->state(fn (): array => ['schedule_enabled' => true]);
    }
}
