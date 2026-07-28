<?php

declare(strict_types=1);

namespace Modules\Students\Application\Commands;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\DB;
use Modules\Students\Application\Support\StudentCacheKeys;
use Modules\Students\Domain\Ports\StudentRepositoryPort;
use Shared\Application\DTOs\BulkUuidsData;

final readonly class BulkRestoreStudentsHandler
{
    public function __construct(
        private StudentRepositoryPort $students,
        private Cache $cache,
    ) {}

    #[\NoDiscard]
    public function handle(BulkUuidsData $data): int
    {
        $count = DB::transaction(fn () => $this->students->bulkRestoreByUuid($data->uuids));

        foreach ($data->uuids as $uuid) {
            $this->cache->forget(StudentCacheKeys::student($uuid));
        }

        return $count;
    }
}
