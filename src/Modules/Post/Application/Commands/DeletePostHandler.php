<?php

declare(strict_types=1);

namespace Modules\Post\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Post\Domain\Ports\PostRepositoryPort;
use Modules\Post\Infrastructure\Cache\PostPublicFeedCache;

/**
 * Soft-deletes a single post by UUID. The cover image is intentionally kept so
 * a restore is lossless. Authorization (permission:DELETE_POSTS) is enforced
 * at the route.
 */
final readonly class DeletePostHandler
{
    public function __construct(private PostRepositoryPort $posts) {}

    public function handle(string $uuid): bool
    {
        $result = DB::transaction(fn () => $this->posts->softDelete($uuid));

        PostPublicFeedCache::flush();

        return $result;
    }
}
