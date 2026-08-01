<?php

declare(strict_types=1);

namespace Modules\Blog\Infrastructure\Cache;

use Illuminate\Support\Facades\Cache;
use Modules\Blog\Application\Queries\ListPublicBlogCategoriesHandler;
use Throwable;

/**
 * Invalidation for the public blog-category feed cache (see
 * {@see ListPublicBlogCategoriesHandler}). Called by every Command handler
 * that mutates a category, so the anonymous landing-page feed never serves
 * stale data past a save. A category's `posts_count` also changes whenever a
 * Post is created/updated/deleted, so the Post module's mutation handlers
 * flush this cache too (see `Modules\Post\Infrastructure\Cache\PostPublicFeedCache`).
 *
 * Tag-based flush degrades silently when the cache store doesn't support tags
 * (BACKEND-PHP §5 Cache Management) — entries still expire via their TTL.
 */
final class BlogCategoryPublicFeedCache
{
    public static function flush(): void
    {
        try {
            Cache::tags(['blog_categories_public'])->flush();
        } catch (Throwable) {
            // Store doesn't support tags — drop the plain fallback keys used by
            // ListPublicBlogCategoriesHandler when Cache::tags() throws.
            Cache::forget('blog_categories.public');
            Cache::forget('blog_categories.public.v2');
        }
    }
}
