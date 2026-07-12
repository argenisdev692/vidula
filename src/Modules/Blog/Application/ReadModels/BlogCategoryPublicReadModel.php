<?php

declare(strict_types=1);

namespace Modules\Blog\Application\ReadModels;

use Modules\Blog\Infrastructure\Persistence\Eloquent\Models\BlogCategoryEloquentModel;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Response shape for the anonymous landing-page category feed. Property-level
 * authorization allowlist (OWASP §12): the public JSON is built ONLY from
 * these fields, so internal columns (`id`, `user_id`) never leak — unlike
 * serializing the Eloquent model directly and relying on `$hidden`.
 */
#[MapOutputName(SnakeCaseMapper::class)]
final class BlogCategoryPublicReadModel extends Data
{
    public function __construct(
        public string $uuid,
        public ?string $name,
        public ?string $description,
        public ?string $imageUrl,
        public int $postsCount,
    ) {}

    public static function fromModel(BlogCategoryEloquentModel $model): self
    {
        return new self(
            uuid: $model->uuid,
            name: $model->blog_category_name,
            description: $model->blog_category_description,
            imageUrl: $model->image_url,
            postsCount: (int) ($model->posts_count ?? 0),
        );
    }
}
