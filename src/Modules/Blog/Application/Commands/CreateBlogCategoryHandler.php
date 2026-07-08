<?php

declare(strict_types=1);

namespace Modules\Blog\Application\Commands;

use Modules\Blog\Application\DTOs\BlogCategoryData;
use Modules\Blog\Domain\Ports\BlogCategoryRepositoryPort;
use Modules\Blog\Infrastructure\Persistence\Eloquent\Models\BlogCategoryEloquentModel;
use Shared\Domain\Ports\StoragePort;

/**
 * Persists a new blog category. When an image is uploaded it is stored on R2
 * with public visibility and the returned object key is written to the
 * `blog_category_image` column. Authorization (permission:CREATE_BLOG_CATEGORIES)
 * is enforced at the route — never inside this handler.
 */
final readonly class CreateBlogCategoryHandler
{
    public function __construct(
        private BlogCategoryRepositoryPort $blogCategories,
        private StoragePort $storage,
    ) {}

    #[\NoDiscard]
    public function handle(BlogCategoryData $data, int $userId): BlogCategoryEloquentModel
    {
        return $this->blogCategories->create([
            'blog_category_name' => $data->name,
            'blog_category_description' => $data->description,
            'blog_category_image' => $data->image !== null
                ? $this->storage->putFile('blog-categories', $data->image, 'public')
                : null,
            'user_id' => $userId,
        ]);
    }
}
