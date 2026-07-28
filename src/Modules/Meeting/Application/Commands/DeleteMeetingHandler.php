<?php

declare(strict_types=1);

namespace Modules\Meeting\Application\Commands;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\DB;
use Modules\Meeting\Domain\Ports\MeetingRepositoryPort;

/**
 * Soft-deletes a single meeting by UUID. Authorization
 * (permission:DELETE_MEETINGS) is enforced at the route.
 */
final readonly class DeleteMeetingHandler
{
    public function __construct(
        private MeetingRepositoryPort $meetings,
        private Cache $cache,
    ) {}

    #[\NoDiscard]
    public function handle(string $uuid): bool
    {
        $result = DB::transaction(fn () => $this->meetings->softDelete($uuid));

        $this->cache->forget("meeting_{$uuid}");

        return $result;
    }
}
