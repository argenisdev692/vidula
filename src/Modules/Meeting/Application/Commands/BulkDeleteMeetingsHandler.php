<?php

declare(strict_types=1);

namespace Modules\Meeting\Application\Commands;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\DB;
use Modules\Meeting\Domain\Ports\MeetingRepositoryPort;
use Shared\Application\DTOs\BulkUuidsData;

/**
 * Soft-deletes a set of meetings by UUID. Route gate:
 * `permission:BULK_DELETE_MEETINGS`. Object-level BOLA: without
 * `VIEW_ANY_MEETINGS`, only the actor's own meetings are affected.
 */
final readonly class BulkDeleteMeetingsHandler
{
    public function __construct(
        private MeetingRepositoryPort $meetings,
        private Cache $cache,
    ) {}

    #[\NoDiscard]
    public function handle(BulkUuidsData $data, int $actorId, bool $canManageAny): int
    {
        $uuids = $canManageAny
            ? $data->uuids
            : $this->meetings->ownedUuidsAmong($data->uuids, $actorId);

        if ($uuids === []) {
            return 0;
        }

        $count = DB::transaction(fn () => $this->meetings->bulkSoftDeleteByUuid($uuids));

        foreach ($uuids as $uuid) {
            $this->cache->forget("meeting_{$uuid}");
        }

        return $count;
    }
}
