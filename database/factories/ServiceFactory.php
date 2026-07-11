<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Services\Infrastructure\Persistence\Eloquent\Models\ServiceEloquentModel;

/**
 * @extends Factory<ServiceEloquentModel>
 */
final class ServiceFactory extends Factory
{
    /**
     * @var class-string<ServiceEloquentModel>
     */
    protected $model = ServiceEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title($this->faker->unique()->words(2, true));

        return [
            'uuid' => (string) Str::uuid7(),
            'name' => $name,
            'slug' => Str::slug($name, '_'),
            'description' => $this->faker->sentence(),
            'is_active' => true,
            'sort_order' => 0,
            'user_id' => User::factory(),
        ];
    }
}
