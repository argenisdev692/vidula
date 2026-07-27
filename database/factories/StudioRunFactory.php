<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\AiResumeStudio\Domain\Enums\StudioMode;
use Modules\AiResumeStudio\Domain\Enums\StudioRunStatus;
use Modules\AiResumeStudio\Domain\Enums\StudioRunStep;
use Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models\StudioRunEloquentModel;
use Modules\Cvs\Infrastructure\Persistence\Eloquent\Models\CvEloquentModel;

/**
 * @extends Factory<StudioRunEloquentModel>
 */
final class StudioRunFactory extends Factory
{
    /**
     * @var class-string<StudioRunEloquentModel>
     */
    protected $model = StudioRunEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid7(),
            'user_id' => User::factory(),
            'cv_id' => CvEloquentModel::factory(),
            'job_search_config_id' => null,
            'mode' => StudioMode::Career->value,
            'step' => StudioRunStep::Queued->value,
            'status' => StudioRunStatus::Pending->value,
            'error_summary' => null,
            'meta' => null,
            'started_at' => null,
            'finished_at' => null,
        ];
    }
}
