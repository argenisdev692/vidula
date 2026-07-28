<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductEloquentModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductSessionEloquentModel;

/**
 * @extends Factory<ProductSessionEloquentModel>
 */
final class ProductSessionFactory extends Factory
{
    /**
     * @var class-string<ProductSessionEloquentModel>
     */
    protected $model = ProductSessionEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid7(),
            'product_id' => ProductEloquentModel::factory(),
            'session_number' => $this->faker->numberBetween(1, 12),
            'title' => $this->faker->sentence(5),
            'session_date' => null,
            'start_time' => null,
            'end_time' => null,
            'hours' => null,
            'notes' => null,
        ];
    }

    public function number(int $sessionNumber): static
    {
        return $this->state(fn (): array => ['session_number' => $sessionNumber]);
    }
}
