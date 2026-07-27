<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models\GithubEnrichmentEloquentModel;
use Modules\Cvs\Infrastructure\Persistence\Eloquent\Models\CvEloquentModel;

/**
 * @extends Factory<GithubEnrichmentEloquentModel>
 */
final class GithubEnrichmentFactory extends Factory
{
    /**
     * @var class-string<GithubEnrichmentEloquentModel>
     */
    protected $model = GithubEnrichmentEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid7(),
            'user_id' => User::factory(),
            'cv_id' => CvEloquentModel::factory(),
            'github_username' => $this->faker->userName(),
            'selected_repos' => ['owner/repo-one'],
            'extra_prompt' => null,
            'repos_summary' => null,
            'last_synced_at' => null,
        ];
    }
}
