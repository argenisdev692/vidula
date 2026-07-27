<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\AiResumeStudio\Domain\Enums\OutreachKind;
use Modules\AiResumeStudio\Domain\Enums\OutreachStatus;
use Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models\OutreachDraftEloquentModel;

/**
 * @extends Factory<OutreachDraftEloquentModel>
 */
final class OutreachDraftFactory extends Factory
{
    /**
     * @var class-string<OutreachDraftEloquentModel>
     */
    protected $model = OutreachDraftEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid7(),
            'user_id' => User::factory(),
            'job_match_id' => null,
            'studio_run_id' => null,
            'kind' => OutreachKind::Cover->value,
            'subject' => 'Application for Senior Laravel Developer',
            'body' => 'Dear hiring manager, I am excited to apply...',
            'language' => 'en',
            'status' => OutreachStatus::Draft->value,
            'provider' => 'openai',
        ];
    }
}
