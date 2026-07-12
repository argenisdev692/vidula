<?php

declare(strict_types=1);

namespace Modules\Post\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Post\Domain\Ports\PostRepositoryPort;
use Modules\Post\Infrastructure\Cache\PostPublicFeedCache;
use Shared\Application\DTOs\BulkUuidsData;

/**
 * Restores a set of soft-deleted posts by UUID. Authorization
 * (permission:BULK_RESTORE_POSTS) is enforced at the route.
 */
final readonly class BulkRestorePostsHandler
{
    public function __construct(private PostRepositoryPort $posts) {}

    public function handle(BulkUuidsData $data): int
    {
        $count = DB::transaction(fn () => $this->posts->bulkRestoreByUuid($data->uuids));

        PostPublicFeedCache::flush();

        return $count;
    }
}
