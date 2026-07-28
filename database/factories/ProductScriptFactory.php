<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Products\Domain\Enums\ScriptStatus;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductScriptEloquentModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductSessionTopicEloquentModel;

/**
 * @extends Factory<ProductScriptEloquentModel>
 */
final class ProductScriptFactory extends Factory
{
    /**
     * @var class-string<ProductScriptEloquentModel>
     */
    protected $model = ProductScriptEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid7(),
            'product_session_topic_id' => ProductSessionTopicEloquentModel::factory(),
            'intro' => $this->faker->paragraph(),
            'body' => $this->faker->paragraphs(3, true),
            'outro' => $this->faker->paragraph(),
            'notes' => null,
            'status' => ScriptStatus::Draft,
            'estimated_minutes' => $this->faker->numberBetween(5, 20),
            'generated_by_model' => null,
            'sources_json' => null,
        ];
    }

    public function generated(): static
    {
        return $this->state(fn (): array => [
            'status' => ScriptStatus::Generated,
            'generated_by_model' => 'gpt-4.1-mini',
        ]);
    }

    public function needsReview(): static
    {
        return $this->state(fn (): array => ['status' => ScriptStatus::NeedsReview]);
    }

    public function verified(): static
    {
        return $this->state(fn (): array => ['status' => ScriptStatus::Verified]);
    }
}
