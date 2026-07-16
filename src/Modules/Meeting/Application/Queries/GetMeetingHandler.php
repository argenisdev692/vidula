<?php

declare(strict_types=1);

namespace Modules\Meeting\Application\Queries;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Meeting\Domain\Ports\MeetingRepositoryPort;
use Modules\Meeting\Infrastructure\Persistence\Eloquent\Models\MeetingEloquentModel;

/**
 * Single-record lookup, cached 15 minutes per UUID (mirrors
 * `GetAppointmentHandler` — BACKEND-PHP §5). Every mutating Meeting handler
 * calls `Cache::forget("meeting_{$uuid}")`.
 */
final readonly class GetMeetingHandler
{
    public function __construct(
        private MeetingRepositoryPort $meetings,
        private Cache $cache,
    ) {}

    public function handle(string $uuid): MeetingEloquentModel
    {
        return $this->cache->remember(
            "meeting_{$uuid}",
            now()->addMinutes(15),
            fn () => $this->meetings->findByUuid($uuid)
                ?? throw (new ModelNotFoundException)->setModel(MeetingEloquentModel::class, [$uuid]),
        );
    }
}
