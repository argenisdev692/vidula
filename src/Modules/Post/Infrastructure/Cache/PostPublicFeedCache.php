<?php

declare(strict_types=1);

namespace Modules\Post\Infrastructure\Cache;

use Illuminate\Support\Facades\Cache;
use Modules\Blog\Infrastructure\Cache\BlogCategoryPublicFeedCache;
use Modules\Post\Application\Queries\GetPublicPostHandler;
use Modules\Post\Application\Queries\ListPublicPostsHandler;
use Modules\Post\Domain\Ports\PostPublicFeedCachePort;
use Throwable;

/**
 * Invalidation for the public post feed + detail cache (see
 * {@see ListPublicPostsHandler} / {@see GetPublicPostHandler}). Called by every
 * Command handler that mutates a post, so the anonymous landing-page feed
 * never serves stale data past a save. Also busts the Blog module's public
 * category cache, since a category's `posts_count` changes whenever a post's
 * status or category assignment changes.
 *
 * Tag-based flush degrades silently when the cache store doesn't support tags
 * (BACKEND-PHP §5 Cache Management) — entries still expire via their TTL.
 */
final class PostPublicFeedCache implements PostPublicFeedCachePort
{
    public function flush(): void
    {
        try {
            Cache::tags(['posts_public'])->flush();
        } catch (Throwable) {
            // Store doesn't support tags — cached entries still expire via TTL.
        }

        BlogCategoryPublicFeedCache::flush();
    }
}
