<?php

declare(strict_types=1);

namespace Modules\Blog\Infrastructure\Persistence\Mappers;

use Modules\Blog\Domain\Entities\Post;
use Modules\Blog\Domain\ValueObjects\PostId;
use Modules\Blog\Infrastructure\Persistence\Eloquent\Models\PostEloquentModel;

final class PostMapper
{
    public static function toDomain(PostEloquentModel $model): Post
    {
        return $model
            |> self::mapToEntity(...);
    }

    private static function mapToEntity(PostEloquentModel $model): Post
    {
        return new Post(
            id: new PostId($model->id),
            uuid: $model->uuid,
            title: $model->post_title,
            slug: $model->post_title_slug,
            content: $model->post_content,
            excerpt: $model->post_excerpt,
            coverImage: $model->post_cover_image,
            metaTitle: $model->meta_title,
            metaDescription: $model->meta_description,
            metaKeywords: $model->meta_keywords,
            categoryId: $model->category_id,
            categoryUuid: $model->category?->uuid,
            categoryName: $model->category?->blog_category_name,
            userId: $model->user_id,
            status: $model->post_status,
            publishedAt: $model->published_at?->toIso8601String(),
            scheduledAt: $model->scheduled_at?->toIso8601String(),
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
            deletedAt: $model->deleted_at?->toIso8601String(),
        );
    }
}
