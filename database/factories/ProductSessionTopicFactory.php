<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductSessionEloquentModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductSessionTopicEloquentModel;

/**
 * @extends Factory<ProductSessionTopicEloquentModel>
 */
final class ProductSessionTopicFactory extends Factory
{
    /**
     * @var class-string<ProductSessionTopicEloquentModel>
     */
    protected $model = ProductSessionTopicEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid7(),
            'product_session_id' => ProductSessionEloquentModel::factory(),
            'title' => $this->faker->sentence(6),
            'description' => null,
            'hours' => null,
            'sort_order' => 1,
            'sources_json' => null,
        ];
    }

    public function grounded(): static
    {
        return $this->state(fn (): array => [
            'sources_json' => [
                ['provider' => 'tavily', 'url' => $this->faker->url(), 'title' => $this->faker->sentence(4)],
            ],
        ]);
    }
}
