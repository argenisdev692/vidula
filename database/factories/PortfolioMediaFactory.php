<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Portfolio\Infrastructure\Persistence\Eloquent\Models\PortfolioEloquentModel;
use Modules\Portfolio\Infrastructure\Persistence\Eloquent\Models\PortfolioMediaEloquentModel;

/**
 * @extends Factory<PortfolioMediaEloquentModel>
 */
final class PortfolioMediaFactory extends Factory
{
    /**
     * @var class-string<PortfolioMediaEloquentModel>
     */
    protected $model = PortfolioMediaEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid7(),
            'portfolio_id' => PortfolioEloquentModel::factory(),
            'path' => 'portfolios/gallery/'.$this->faker->uuid().'.jpg',
            'sort_order' => 0,
        ];
    }
}
