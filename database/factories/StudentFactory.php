<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Students\Infrastructure\Persistence\Eloquent\Models\StudentEloquentModel;

/**
 * @extends Factory<StudentEloquentModel>
 */
final class StudentFactory extends Factory
{
    /**
     * @var class-string<StudentEloquentModel>
     */
    protected $model = StudentEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid7(),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => '+15551234567',
            'dni' => null,
            'address' => $this->faker->streetAddress(),
            'avatar' => null,
            'notes' => null,
            'status' => 'DRAFT',
            'active' => true,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => ['status' => 'ACTIVE', 'active' => true]);
    }

    public function archived(): static
    {
        return $this->state(fn (): array => ['status' => 'ARCHIVED', 'active' => false]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['active' => false]);
    }
}
