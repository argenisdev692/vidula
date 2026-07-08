<?php

declare(strict_types=1);

namespace Modules\Blog\Application\Commands;

use Modules\Blog\Application\DTOs\BlogCategoryData;
use Modules\Blog\Domain\Ports\BlogCategoryRepositoryPort;
use Modules\Blog\Infrastructure\Persistence\Eloquent\Models\BlogCategoryEloquentModel;
use Shared\Domain\Ports\StoragePort;

/**
 * Updates an existing blog category. A new image replaces the previous R2 object
 * (the old key is deleted so the bucket never accumulates orphans); when no image
 * is uploaded the existing column is left untouched. Authorization
 * (permission:UPDATE_BLOG_CATEGORIES) is enforced at the route.
 */
final readonly class UpdateBlogCategoryHandler
{
    public function __construct(
        private BlogCategoryRepositoryPort $blogCategories,
        private StoragePort $storage,
    ) {}

    public function handle(BlogCategoryEloquentModel $category, BlogCategoryData $data): BlogCategoryEloquentModel
    {
        $attributes = [
            'blog_category_name' => $data->name,
            'blog_category_description' => $data->description,
        ];

        if ($data->image !== null) {
            /** @var string|null $previous */
            $previous = $category->blog_category_image;
            $path = $this->storage->putFile('blog-categories', $data->image, 'public');

            if ($previous !== null && $previous !== '' && $previous !== $path) {
                $this->storage->delete($previous);
            }

            $attributes['blog_category_image'] = $path;
        }

        return $this->blogCategories->update($category, $attributes);
    }
}
