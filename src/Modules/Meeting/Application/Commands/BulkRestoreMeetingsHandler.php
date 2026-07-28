<?php

declare(strict_types=1);

namespace Modules\Meeting\Application\Commands;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\DB;
use Modules\Meeting\Domain\Ports\MeetingRepositoryPort;
use Shared\Application\DTOs\BulkUuidsData;

/**
 * Restores a set of meetings by UUID. Route gate:
 * `permission:BULK_RESTORE_MEETINGS`. Object-level BOLA: without
 * `VIEW_ANY_MEETINGS`, only the actor's own soft-deleted meetings are restored.
 */
final readonly class BulkRestoreMeetingsHandler
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
            : $this->meetings->ownedUuidsAmong($data->uuids, $actorId, onlyTrashed: true);

        if ($uuids === []) {
            return 0;
        }

        $count = DB::transaction(fn () => $this->meetings->bulkRestoreByUuid($uuids));

        foreach ($uuids as $uuid) {
            $this->cache->forget("meeting_{$uuid}");
        }

        return $count;
    }
}
