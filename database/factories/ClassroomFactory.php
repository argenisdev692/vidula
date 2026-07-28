<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ClassroomEloquentModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductEloquentModel;

/**
 * @extends Factory<ClassroomEloquentModel>
 */
final class ClassroomFactory extends Factory
{
    /**
     * @var class-string<ClassroomEloquentModel>
     */
    protected $model = ClassroomEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid7(),
            'product_id' => ProductEloquentModel::factory()->classroom(),
            'max_students' => $this->faker->numberBetween(8, 30),
            'meet_url' => null,
            'objectives' => $this->faker->paragraph(),
            'requirements' => $this->faker->paragraph(),
        ];
    }
}
