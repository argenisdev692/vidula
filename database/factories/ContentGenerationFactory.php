<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Products\Domain\Enums\GenerationStatus;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ContentGenerationEloquentModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductEloquentModel;

/**
 * @extends Factory<ContentGenerationEloquentModel>
 */
final class ContentGenerationFactory extends Factory
{
    /**
     * @var class-string<ContentGenerationEloquentModel>
     */
    protected $model = ContentGenerationEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid7(),
            'product_id' => ProductEloquentModel::factory(),
            'user_id' => User::factory(),
            'status' => GenerationStatus::Pending,
            'mode' => 'replace',
            'source_markdown' => "### Sesión 1 | Introducción\n- **Tema 1:** Primer tema\n",
            'model' => null,
            'progress' => 0,
            'sessions_count' => 0,
            'topics_count' => 0,
            'scripts_count' => 0,
            'pdf_path' => null,
            'md_path' => null,
            'zip_path' => null,
            'error' => null,
            'started_at' => null,
            'completed_at' => null,
        ];
    }

    public function inFlight(): static
    {
        return $this->state(fn (): array => [
            'status' => GenerationStatus::Generating,
            'progress' => 40,
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => GenerationStatus::Completed,
            'progress' => 100,
            'sessions_count' => 3,
            'topics_count' => 12,
            'scripts_count' => 12,
            'started_at' => now()->subMinutes(10),
            'completed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (): array => [
            'status' => GenerationStatus::Failed,
            'error' => 'UNPARSEABLE_MARKDOWN',
            'started_at' => now()->subMinutes(2),
            'completed_at' => now(),
        ]);
    }
}
