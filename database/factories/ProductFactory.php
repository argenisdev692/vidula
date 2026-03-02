<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductEloquentModel;
use Ramsey\Uuid\Uuid;

class ProductFactory extends Factory
{
    protected $model = ProductEloquentModel::class;

    public function definition(): array
    {
        return [
            'uuid' => Uuid::uuid4()->toString(),
            'user_id' => 1,
            'type' => $this->faker->randomElement(['classroom', 'video']),
            'title' => $this->faker->words(3, true),
            'slug' => $this->faker->unique()->slug(),
            'description' => $this->faker->paragraph(),
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'currency' => 'EUR',
            'status' => $this->faker->randomElement(['draft', 'published', 'archived']),
            'thumbnail' => $this->faker->imageUrl(),
            'level' => $this->faker->randomElement(['beginner', 'intermediate', 'advanced']),
            'language' => $this->faker->randomElement(['es', 'en']),
        ];
    }
}
