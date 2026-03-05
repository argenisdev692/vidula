<?php

declare(strict_types=1);

namespace Modules\Blog\Infrastructure\Persistence\Mappers;

use Modules\Blog\Domain\Entities\BlogCategory;
use Modules\Blog\Domain\ValueObjects\BlogCategoryId;
use Modules\Blog\Infrastructure\Persistence\Eloquent\Models\BlogCategoryEloquentModel;

/**
 * BlogCategoryMapper — Translates between Eloquent model and Domain entity.
 */
final class BlogCategoryMapper
{
    public static function toDomain(BlogCategoryEloquentModel $model): BlogCategory
    {
        return $model
            |> self::mapToEntity(...);
    }

    private static function mapToEntity(BlogCategoryEloquentModel $model): BlogCategory
    {
        return new BlogCategory(
            id: new BlogCategoryId($model->id),
            uuid: $model->uuid,
            name: $model->blog_category_name ?? '',
            description: $model->blog_category_description,
            image: $model->blog_category_image,
            userId: $model->user_id,
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
            deletedAt: $model->deleted_at?->toIso8601String(),
        );
    }
}
