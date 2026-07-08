<?php

declare(strict_types=1);

namespace Modules\Blog\Application\Commands;

use Modules\Blog\Domain\Ports\BlogCategoryRepositoryPort;

/**
 * Soft-deletes a single blog category by UUID. The image object is intentionally
 * kept so a restore is lossless. Authorization (permission:DELETE_BLOG_CATEGORIES)
 * is enforced at the route.
 */
final readonly class DeleteBlogCategoryHandler
{
    public function __construct(private BlogCategoryRepositoryPort $blogCategories) {}

    public function handle(string $uuid): bool
    {
        return $this->blogCategories->softDelete($uuid);
    }
}
