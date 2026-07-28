<?php

declare(strict_types=1);

namespace Modules\Meeting\Application\Commands;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\DB;
use Modules\Meeting\Domain\Ports\MeetingRepositoryPort;

final readonly class RestoreMeetingHandler
{
    public function __construct(
        private MeetingRepositoryPort $meetings,
        private Cache $cache,
    ) {}

    #[\NoDiscard]
    public function handle(string $uuid): bool
    {
        $result = DB::transaction(fn () => $this->meetings->restore($uuid));

        $this->cache->forget("meeting_{$uuid}");

        return $result;
    }
}
