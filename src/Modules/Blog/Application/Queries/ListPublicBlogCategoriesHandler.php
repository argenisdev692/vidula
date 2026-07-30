<?php

declare(strict_types=1);

namespace Modules\Blog\Application\Queries;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Blog\Application\ReadModels\BlogCategoryPublicReadModel;
use Modules\Blog\Domain\Ports\BlogCategoryRepositoryPort;
use Modules\Post\Infrastructure\Cache\PostPublicFeedCache;
use Throwable;

/**
 * Landing-page category selector: every active category with its published
 * post count, no authentication required. Kept as its own query (rather than
 * reusing {@see ListBlogCategoriesHandler}) since the trust boundary and
 * column allowlist genuinely diverge — this one is reachable by anonymous
 * internet traffic (BACKEND-PHP §7 Insecure Design).
 *
 * Cached, unlike the admin list: this is hit by anonymous traffic with no
 * per-user throttle beyond the route's `throttle:60,1` (BACKEND-PHP §5 Cache
 * Management). Every category-mutating handler AND every Post-mutating
 * handler (via {@see PostPublicFeedCache})
 * busts the `blog_categories_public` tag, since `posts_count` depends on both.
 */
final readonly class ListPublicBlogCategoriesHandler
{
    private const int TTL_MINUTES = 10;

    private const string CACHE_KEY = 'blog_categories.public';

    public function __construct(private BlogCategoryRepositoryPort $categories) {}

    /**
     * @return Collection<int, BlogCategoryPublicReadModel>
     */
    public function handle(): Collection
    {
        try {
            $categories = Cache::tags(['blog_categories_public'])->remember(
                self::CACHE_KEY,
                now()->addMinutes(self::TTL_MINUTES),
                fn () => $this->categories->listPublic(),
            );
        } catch (Throwable) {
            // Array/file stores reject tags — always forget+remember on the plain key
            // so tests (CACHE_STORE=array) never serve a stale empty feed.
            $categories = Cache::remember(
                self::CACHE_KEY,
                now()->addMinutes(self::TTL_MINUTES),
                fn () => $this->categories->listPublic(),
            );
        }

        return $categories->map(BlogCategoryPublicReadModel::fromModel(...));
    }
}
