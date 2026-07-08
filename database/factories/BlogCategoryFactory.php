<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Blog\Infrastructure\Persistence\Eloquent\Models\BlogCategoryEloquentModel;

/**
 * @extends Factory<BlogCategoryEloquentModel>
 */
final class BlogCategoryFactory extends Factory
{
    /**
     * @var class-string<BlogCategoryEloquentModel>
     */
    protected $model = BlogCategoryEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid7(),
            'blog_category_name' => Str::title($this->faker->unique()->words(2, true)),
            'blog_category_description' => $this->faker->sentence(),
            'blog_category_image' => null,
            'user_id' => User::factory(),
        ];
    }
}
