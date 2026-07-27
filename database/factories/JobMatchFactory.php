<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\AiResumeStudio\Domain\Enums\ApplicationStatus;
use Modules\AiResumeStudio\Domain\Enums\JobMatchSource;
use Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models\JobMatchEloquentModel;

/**
 * @extends Factory<JobMatchEloquentModel>
 */
final class JobMatchFactory extends Factory
{
    /**
     * @var class-string<JobMatchEloquentModel>
     */
    protected $model = JobMatchEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $url = 'https://example.com/jobs/'.$this->faker->uuid();

        return [
            'uuid' => (string) Str::uuid7(),
            'user_id' => User::factory(),
            'job_search_config_id' => null,
            'studio_run_id' => null,
            'job_title' => 'Senior Laravel Developer',
            'company_name' => 'Acme Corp',
            'job_url' => $url,
            'canonical_url' => $url,
            'raw_snippet' => 'We are hiring a Laravel developer.',
            'raw_md' => null,
            'match_score' => 75,
            'match_reasoning' => 'Strong stack overlap.',
            'source' => JobMatchSource::Tavily->value,
            'application_status' => ApplicationStatus::New->value,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ];
    }
}
