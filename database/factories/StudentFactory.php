<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Students\Infrastructure\Persistence\Eloquent\Models\StudentEloquentModel;
use Ramsey\Uuid\Uuid;

class StudentFactory extends Factory
{
    protected $model = StudentEloquentModel::class;

    public function definition(): array
    {
        return [
            'uuid' => Uuid::uuid4()->toString(),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'dni' => $this->faker->unique()->numerify('#########'),
            'birth_date' => $this->faker->date('Y-m-d', '-18 years'),
            'address' => $this->faker->address(),
            'avatar' => null,
            'notes' => $this->faker->paragraph(),
            'active' => $this->faker->boolean(80),
        ];
    }
}
