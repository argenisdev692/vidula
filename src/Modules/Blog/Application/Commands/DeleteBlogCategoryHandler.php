<?php

declare(strict_types=1);

namespace Modules\Blog\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Blog\Domain\Ports\BlogCategoryRepositoryPort;
use Modules\Blog\Infrastructure\Cache\BlogCategoryPublicFeedCache;

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
        $result = DB::transaction(fn () => $this->blogCategories->softDelete($uuid));

        BlogCategoryPublicFeedCache::flush();

        return $result;
    }
}
