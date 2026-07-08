<?php

declare(strict_types=1);

namespace Modules\Blog\Application\Commands;

use Modules\Blog\Domain\Ports\BlogCategoryRepositoryPort;

/**
 * Restores a soft-deleted blog category by UUID. Authorization
 * (permission:RESTORE_BLOG_CATEGORIES) is enforced at the route.
 */
final readonly class RestoreBlogCategoryHandler
{
    public function __construct(private BlogCategoryRepositoryPort $blogCategories) {}

    public function handle(string $uuid): bool
    {
        return $this->blogCategories->restore($uuid);
    }
}
