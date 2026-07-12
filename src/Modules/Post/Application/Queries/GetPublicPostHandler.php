<?php

declare(strict_types=1);

namespace Modules\Post\Application\Queries;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;
use Modules\Post\Application\ReadModels\PostPublicReadModel;
use Modules\Post\Domain\Ports\PostRepositoryPort;
use Modules\Post\Infrastructure\Persistence\Eloquent\Models\PostEloquentModel;
use Throwable;

/**
 * Single-post landing-page detail view (by slug): `published` only, no
 * authentication required. Mirrors {@see ListPublicPostsHandler}'s trust
 * boundary and caching rationale, but includes the full `content` field.
 */
final readonly class GetPublicPostHandler
{
    private const int TTL_MINUTES = 5;

    public function __construct(private PostRepositoryPort $posts) {}

    public function handle(string $slug): PostPublicReadModel
    {
        $cacheKey = "posts.public.slug.{$slug}";

        try {
            $post = Cache::tags(['posts_public'])->remember(
                $cacheKey,
                now()->addMinutes(self::TTL_MINUTES),
                fn () => $this->posts->findPublicBySlug($slug),
            );
        } catch (Throwable) {
            $post = Cache::remember(
                $cacheKey,
                now()->addMinutes(self::TTL_MINUTES),
                fn () => $this->posts->findPublicBySlug($slug),
            );
        }

        if (! $post instanceof PostEloquentModel) {
            throw (new ModelNotFoundException)->setModel(PostEloquentModel::class, [$slug]);
        }

        return PostPublicReadModel::fromModel($post, includeContent: true);
    }
}
