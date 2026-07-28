<?php

declare(strict_types=1);

namespace Modules\Students\Application\Commands;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\DB;
use Modules\Students\Application\Support\StudentCacheKeys;
use Modules\Students\Domain\Ports\StudentRepositoryPort;

final readonly class DeleteStudentHandler
{
    public function __construct(
        private StudentRepositoryPort $students,
        private Cache $cache,
    ) {}

    #[\NoDiscard]
    public function handle(string $uuid): bool
    {
        $result = DB::transaction(fn () => $this->students->softDelete($uuid));

        $this->cache->forget(StudentCacheKeys::student($uuid));

        return $result;
    }
}
