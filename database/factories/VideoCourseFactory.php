<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Products\Domain\Enums\VideoPlatform;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductEloquentModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\VideoCourseEloquentModel;

/**
 * @extends Factory<VideoCourseEloquentModel>
 */
final class VideoCourseFactory extends Factory
{
    /**
     * @var class-string<VideoCourseEloquentModel>
     */
    protected $model = VideoCourseEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid7(),
            'product_id' => ProductEloquentModel::factory()->videoTutorial(),
            'platform' => VideoPlatform::Youtube,
            'playlist_url' => null,
            'total_videos' => $this->faker->numberBetween(5, 60),
            'total_duration_minutes' => $this->faker->numberBetween(30, 600),
            'target_audience' => $this->faker->sentence(),
        ];
    }
}
