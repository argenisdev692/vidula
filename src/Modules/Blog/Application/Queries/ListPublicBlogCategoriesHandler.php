<?php

declare(strict_types=1);

namespace Modules\Blog\Application\Queries;

use Closure;
use Illuminate\Support\Facades\Cache;
use Modules\Blog\Application\ReadModels\BlogCategoryPublicReadModel;
use Modules\Blog\Domain\Ports\BlogCategoryRepositoryPort;
use Modules\Blog\Infrastructure\Persistence\Eloquent\Models\BlogCategoryEloquentModel;
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
 * per-user throttle beyond the route's `throttle:landing-public` (BACKEND-PHP §5 Cache
 * Management). Every category-mutating handler AND every Post-mutating
 * handler (via {@see PostPublicFeedCache})
 * busts the `blog_categories_public` tag, since `posts_count` depends on both.
 *
 * Important: we cache the already-mapped public arrays — never Eloquent models.
 * Serializing models + accessors (R2) across Redis was a source of sticky 500s
 * after the first warm; bumping the key also busts any poisoned payload.
 */
final readonly class ListPublicBlogCategoriesHandler
{
    private const int TTL_MINUTES = 10;

    /** Bumped when the cached payload shape / mapping strategy changes. */
    private const string CACHE_KEY = 'blog_categories.public.v2';

    public function __construct(private BlogCategoryRepositoryPort $categories) {}

    /**
     * @return list<array{uuid: string, name: string|null, description: string|null, image_url: string|null, posts_count: int}>
     */
    public function handle(): array
    {
        return $this->remember(
            function (): array {
                return $this->categories
                    ->listPublic()
                    ->map(static function (BlogCategoryEloquentModel $model): array {
                        return BlogCategoryPublicReadModel::fromModel($model)->toArray();
                    })
                    ->values()
                    ->all();
            },
        );
    }

    /**
     * @param  Closure(): list<array{uuid: string, name: string|null, description: string|null, image_url: string|null, posts_count: int}>  $callback
     * @return list<array{uuid: string, name: string|null, description: string|null, image_url: string|null, posts_count: int}>
     */
    private function remember(Closure $callback): array
    {
        try {
            /** @var list<array{uuid: string, name: string|null, description: string|null, image_url: string|null, posts_count: int}> */
            return Cache::tags(['blog_categories_public'])->remember(
                self::CACHE_KEY,
                now()->addMinutes(self::TTL_MINUTES),
                $callback,
            );
        } catch (Throwable) {
            // Array/file stores reject tags — always forget+remember on the plain key
            // so tests (CACHE_STORE=array) never serve a stale empty feed.
            /** @var list<array{uuid: string, name: string|null, description: string|null, image_url: string|null, posts_count: int}> */
            return Cache::remember(
                self::CACHE_KEY,
                now()->addMinutes(self::TTL_MINUTES),
                $callback,
            );
        }
    }
}
