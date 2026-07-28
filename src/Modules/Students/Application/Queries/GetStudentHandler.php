<?php

declare(strict_types=1);

namespace Modules\Students\Application\Queries;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Students\Application\Support\StudentCacheKeys;
use Modules\Students\Domain\Ports\StudentRepositoryPort;
use Modules\Students\Infrastructure\Persistence\Eloquent\Models\StudentEloquentModel;

/**
 * Single-record lookup, cached 15 minutes per UUID (mirrors Clients/Invoices).
 * Mutating handlers forget {@see StudentCacheKeys::student()}.
 */
final readonly class GetStudentHandler
{
    public function __construct(
        private StudentRepositoryPort $students,
        private Cache $cache,
    ) {}

    public function handle(string $uuid): StudentEloquentModel
    {
        return $this->cache->remember(
            StudentCacheKeys::student($uuid),
            now()->addMinutes(15),
            fn () => $this->students->findByUuid($uuid)
                ?? throw (new ModelNotFoundException)->setModel(StudentEloquentModel::class, [$uuid]),
        );
    }
}
