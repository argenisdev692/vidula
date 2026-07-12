<?php

declare(strict_types=1);

namespace Modules\Appointment\Domain\Ports;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Appointment\Application\DTOs\AppointmentFilterData;
use Modules\Appointment\Infrastructure\Persistence\Eloquent\Models\AppointmentEloquentModel;

interface AppointmentRepositoryPort
{
    /**
     * @return LengthAwarePaginator<int, AppointmentEloquentModel>
     */
    public function paginate(AppointmentFilterData $filters, int $perPage): LengthAwarePaginator;

    public function findByUuid(string $uuid): ?AppointmentEloquentModel;

    /**
     * The single active (non-deleted) lead for an email, if any — enforces the
     * "one active lead per email" invariant backed by the DB partial unique index.
     */
    public function findActiveByEmail(string $email): ?AppointmentEloquentModel;

    /**
     * Whether another active, non-cancelled appointment already occupies this
     * exact timestamp. `$excludeUuid` lets a reschedule/confirm re-check the
     * SAME row without tripping on itself.
     */
    public function hasConflict(CarbonInterface $scheduledAt, ?string $excludeUuid = null): bool;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): AppointmentEloquentModel;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(AppointmentEloquentModel $appointment, array $attributes): AppointmentEloquentModel;

    public function markAsRead(string $uuid): bool;

    public function softDelete(string $uuid): bool;

    public function restore(string $uuid): bool;

    /**
     * @param  array<int, string>  $uuids
     */
    public function bulkSoftDeleteByUuid(array $uuids): int;

    /**
     * @param  array<int, string>  $uuids
     */
    public function bulkRestoreByUuid(array $uuids): int;
}
