<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Products\Domain\Enums\ProductModality;
use Modules\Products\Domain\Enums\ProductStatus;
use Modules\Products\Domain\Enums\ProductType;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductEloquentModel;

/**
 * @extends Factory<ProductEloquentModel>
 */
final class ProductFactory extends Factory
{
    /**
     * @var class-string<ProductEloquentModel>
     */
    protected $model = ProductEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->unique()->sentence(4);

        return [
            'uuid' => (string) Str::uuid7(),
            'user_id' => User::factory(),
            'client_id' => null,
            'type' => ProductType::Classroom,
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(6)),
            'description' => $this->faker->paragraph(),
            'price' => $this->faker->randomFloat(2, 100, 5000),
            'currency' => 'EUR',
            'status' => ProductStatus::Draft,
            'thumbnail' => null,
            'level' => 'beginner',
            'language' => 'es',
            'start_date' => null,
            'end_date' => null,
            'total_hours' => null,
            'total_sessions' => null,
            'modality' => null,
            'notes' => null,
        ];
    }

    public function classroom(): static
    {
        return $this->state(fn (): array => [
            'type' => ProductType::Classroom,
            'modality' => ProductModality::Online,
            'total_sessions' => 8,
            'total_hours' => 24.00,
        ]);
    }

    public function videoTutorial(): static
    {
        return $this->state(fn (): array => [
            'type' => ProductType::VideoTutorial,
            'modality' => null,
        ]);
    }

    public function videoPill(): static
    {
        return $this->state(fn (): array => [
            'type' => ProductType::VideoPill,
            'modality' => null,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (): array => ['status' => ProductStatus::Published]);
    }

    public function archived(): static
    {
        return $this->state(fn (): array => ['status' => ProductStatus::Archived]);
    }
}
