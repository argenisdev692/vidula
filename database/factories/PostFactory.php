<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Post\Domain\Enums\PostStatus;
use Modules\Post\Infrastructure\Persistence\Eloquent\Models\PostEloquentModel;

/**
 * @extends Factory<PostEloquentModel>
 */
final class PostFactory extends Factory
{
    /**
     * @var class-string<PostEloquentModel>
     */
    protected $model = PostEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = Str::title($this->faker->unique()->sentence(4));

        return [
            'uuid' => (string) Str::uuid7(),
            'post_title' => $title,
            'post_title_slug' => Str::slug($title).'-'.$this->faker->unique()->randomNumber(5),
            'post_content' => $this->faker->paragraphs(6, true),
            'post_excerpt' => $this->faker->sentence(20),
            'post_cover_image' => null,
            'meta_title' => $title,
            'meta_description' => $this->faker->sentence(15),
            'meta_keywords' => implode(', ', $this->faker->words(5)),
            'category_id' => null,
            'user_id' => User::factory(),
            'post_status' => PostStatus::Draft,
            'scheduled_at' => null,
            'published_at' => null,
            'is_ai_generated' => false,
            'ai_provider' => null,
            'ai_generated_at' => null,
            'seo_score' => null,
            'eeat_score' => null,
            'human_writing_index' => null,
            'ai_detection_risk' => null,
            'ai_scores' => null,
        ];
    }

    public function published(): self
    {
        return $this->state(fn (): array => [
            'post_status' => PostStatus::Published,
            'published_at' => now(),
        ]);
    }
}
