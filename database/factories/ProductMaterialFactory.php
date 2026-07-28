<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Products\Domain\Enums\MaterialType;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductEloquentModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductMaterialEloquentModel;

/**
 * @extends Factory<ProductMaterialEloquentModel>
 */
final class ProductMaterialFactory extends Factory
{
    /**
     * @var class-string<ProductMaterialEloquentModel>
     */
    protected $model = ProductMaterialEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid7(),
            'product_id' => ProductEloquentModel::factory(),
            'product_session_topic_id' => null,
            'title' => $this->faker->sentence(3),
            'type' => MaterialType::Markdown,
            'storage_disk' => 'local',
            'path' => 'products/'.Str::uuid7().'/course.md',
            'original_name' => 'course.md',
            'mime_type' => 'text/markdown',
            'size_bytes' => $this->faker->numberBetween(500, 500_000),
            'url' => null,
            'content' => null,
            'is_downloadable' => true,
            'sort_order' => 0,
        ];
    }

    public function pdf(): static
    {
        return $this->state(fn (): array => [
            'type' => MaterialType::Pdf,
            'path' => 'products/'.Str::uuid7().'/course.pdf',
            'original_name' => 'course.pdf',
            'mime_type' => 'application/pdf',
        ]);
    }

    public function link(): static
    {
        return $this->state(fn (): array => [
            'type' => MaterialType::Link,
            'storage_disk' => null,
            'path' => null,
            'original_name' => null,
            'mime_type' => null,
            'size_bytes' => null,
            'url' => $this->faker->url(),
        ]);
    }
}
