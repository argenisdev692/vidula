<?php

declare(strict_types=1);

namespace Modules\Blog\Application\Queries\GetBlogCategory;

use Illuminate\Support\Facades\Cache;
use Modules\Blog\Application\Queries\ReadModels\BlogCategoryReadModel;
use Modules\Blog\Domain\Exceptions\BlogCategoryNotFoundException;
use Modules\Blog\Domain\Ports\BlogCategoryRepositoryPort;

/**
 * GetBlogCategoryHandler — Returns a BlogCategoryReadModel or throws.
 */
final readonly class GetBlogCategoryHandler
{
    public function __construct(
        private BlogCategoryRepositoryPort $repository,
    ) {
    }

    public function handle(GetBlogCategoryQuery $query): BlogCategoryReadModel
    {
        $cacheKey = "blog_category_read_{$query->uuid}";
        $ttl = 60 * 15;

        return Cache::remember($cacheKey, $ttl, function () use ($query) {
            $category = $this->repository->findByUuid($query->uuid);

            if ($category === null) {
                throw BlogCategoryNotFoundException::forUuid($query->uuid);
            }

            return new BlogCategoryReadModel(
                uuid: $category->uuid,
                blogCategoryName: $category->name,
                blogCategoryDescription: $category->description,
                blogCategoryImage: $category->image,
                userId: $category->userId,
                createdAt: $category->createdAt,
                updatedAt: $category->updatedAt,
                deletedAt: $category->deletedAt,
            );
        });
    }
}
