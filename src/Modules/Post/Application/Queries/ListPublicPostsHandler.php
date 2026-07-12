<?php

declare(strict_types=1);

namespace Modules\Post\Application\Queries;

use Illuminate\Support\Facades\Cache;
use Modules\Post\Application\ReadModels\PostPublicReadModel;
use Modules\Post\Domain\Ports\PostRepositoryPort;
use Modules\Post\Infrastructure\Cache\PostPublicFeedCache;
use Spatie\LaravelData\PaginatedDataCollection;
use Throwable;

/**
 * Landing-page feed: `published` posts only, optionally scoped to one
 * category, no authentication required. Kept as its own query (rather than
 * reusing {@see ListPostsHandler}) since the trust boundary and column
 * allowlist genuinely diverge — this one is reachable by anonymous internet
 * traffic (BACKEND-PHP §7 Insecure Design).
 *
 * Cached, unlike the admin `ListPostsHandler`/`GetPostHandler`: this is hit by
 * anonymous traffic with no per-user throttle beyond the route's
 * `throttle:60,1` (BACKEND-PHP §5 Cache Management). Every Create/Update/
 * Delete/Restore/Bulk handler busts the `posts_public` tag via
 * {@see PostPublicFeedCache}.
 */
final readonly class ListPublicPostsHandler
{
    private const int TTL_MINUTES = 5;

    public function __construct(private PostRepositoryPort $posts) {}

    /**
     * @return PaginatedDataCollection<int, PostPublicReadModel>
     */
    public function handle(?string $categoryUuid, int $perPage = 15): PaginatedDataCollection
    {
        $page = max((int) request()->integer('page', 1), 1);
        $cacheKey = "posts.public.category.{$categoryUuid}.page.{$page}.per_page.{$perPage}";

        try {
            $paginator = Cache::tags(['posts_public'])->remember(
                $cacheKey,
                now()->addMinutes(self::TTL_MINUTES),
                fn () => $this->posts->paginatePublic($categoryUuid, $perPage),
            );
        } catch (Throwable) {
            $paginator = Cache::remember(
                $cacheKey,
                now()->addMinutes(self::TTL_MINUTES),
                fn () => $this->posts->paginatePublic($categoryUuid, $perPage),
            );
        }

        // Map to the public allowlist AFTER the cache boundary: the cache keeps
        // storing the Eloquent paginator (unchanged, proven behaviour) while the
        // response shape is a precise, id-free ReadModel collection.
        return PostPublicReadModel::collect($paginator, PaginatedDataCollection::class);
    }
}
