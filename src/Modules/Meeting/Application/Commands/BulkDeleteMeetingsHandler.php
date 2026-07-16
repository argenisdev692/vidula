<?php

declare(strict_types=1);

namespace Modules\Meeting\Application\Commands;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\DB;
use Modules\Meeting\Domain\Ports\MeetingRepositoryPort;
use Shared\Application\DTOs\BulkUuidsData;

/**
 * Soft-deletes a set of meetings by UUID. Authorization
 * (permission:BULK_DELETE_MEETINGS) is enforced at the route.
 */
final readonly class BulkDeleteMeetingsHandler
{
    public function __construct(
        private MeetingRepositoryPort $meetings,
        private Cache $cache,
    ) {}

    public function handle(BulkUuidsData $data): int
    {
        $count = DB::transaction(fn () => $this->meetings->bulkSoftDeleteByUuid($data->uuids));

        foreach ($data->uuids as $uuid) {
            $this->cache->forget("meeting_{$uuid}");
        }

        return $count;
    }
}
