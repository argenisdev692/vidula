<?php

declare(strict_types=1);

namespace Modules\Post\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Post\Domain\Ports\PostRepositoryPort;
use Modules\Post\Infrastructure\Cache\PostPublicFeedCache;

/**
 * Restores a soft-deleted post by UUID. Authorization (permission:RESTORE_POSTS)
 * is enforced at the route.
 */
final readonly class RestorePostHandler
{
    public function __construct(private PostRepositoryPort $posts) {}

    public function handle(string $uuid): bool
    {
        $result = DB::transaction(fn () => $this->posts->restore($uuid));

        PostPublicFeedCache::flush();

        return $result;
    }
}
