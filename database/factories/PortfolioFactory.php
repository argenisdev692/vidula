<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Portfolio\Infrastructure\Persistence\Eloquent\Models\PortfolioEloquentModel;

/**
 * @extends Factory<PortfolioEloquentModel>
 */
final class PortfolioFactory extends Factory
{
    /**
     * @var class-string<PortfolioEloquentModel>
     */
    protected $model = PortfolioEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid7(),
            'title' => Str::title($this->faker->unique()->words(3, true)),
            'client_name' => $this->faker->company(),
            'project_type' => $this->faker->randomElement(['web', 'mobile', 'branding', 'ecommerce']),
            'live_url' => $this->faker->url(),
            'published_at' => $this->faker->dateTimeBetween('-1 year'),
            'is_public' => true,
            'cover_path' => null,
            'video_path' => null,
            'description' => $this->faker->paragraph(),
            'sort_order' => 0,
            'user_id' => User::factory(),
        ];
    }
}
