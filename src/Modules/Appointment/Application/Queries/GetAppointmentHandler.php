<?php

declare(strict_types=1);

namespace Modules\Appointment\Application\Queries;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Appointment\Domain\Ports\AppointmentRepositoryPort;
use Modules\Appointment\Infrastructure\Persistence\Eloquent\Models\AppointmentEloquentModel;

/**
 * Single-record lookup, cached 15 minutes per UUID (BACKEND-PHP §5 Cache
 * Management). Every mutating Appointment handler calls
 * `Cache::forget("appointment_{$uuid}")` so a stale read is never served after
 * an update, status change, or soft delete/restore.
 */
final readonly class GetAppointmentHandler
{
    public function __construct(
        private AppointmentRepositoryPort $appointments,
        private Cache $cache,
    ) {}

    public function handle(string $uuid): AppointmentEloquentModel
    {
        return $this->cache->remember(
            "appointment_{$uuid}",
            now()->addMinutes(15),
            fn () => $this->appointments->findByUuid($uuid)
                ?? throw (new ModelNotFoundException)->setModel(AppointmentEloquentModel::class, [$uuid]),
        );
    }
}
