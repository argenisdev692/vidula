<?php

declare(strict_types=1);

namespace Modules\Appointment\Infrastructure\Persistence\Repositories;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Appointment\Application\DTOs\AppointmentFilterData;
use Modules\Appointment\Domain\Ports\AppointmentRepositoryPort;
use Modules\Appointment\Domain\ValueObjects\MeetingStatus;
use Modules\Appointment\Infrastructure\Persistence\Eloquent\Models\AppointmentEloquentModel;
use Shared\Infrastructure\Persistence\Concerns\BulkSoftDeletesByUuid;

/**
 * Eloquent adapter for {@see AppointmentRepositoryPort}. Bulk soft-delete /
 * restore are inherited from the Shared {@see BulkSoftDeletesByUuid} trait (DRY,
 * mirrors ContactSupport / AvailabilityRule).
 */
final readonly class EloquentAppointmentRepository implements AppointmentRepositoryPort
{
    use BulkSoftDeletesByUuid;

    /**
     * @return class-string<AppointmentEloquentModel>
     */
    protected function model(): string
    {
        return AppointmentEloquentModel::class;
    }

    public function paginate(AppointmentFilterData $filters, int $perPage): LengthAwarePaginator
    {
        return AppointmentEloquentModel::query()
            ->when($filters->status === 'suspended', fn ($q) => $q->onlyTrashed())
            ->applyFilters($filters)
            ->select([
                'id', 'uuid', 'first_name', 'last_name', 'client_type', 'company_name',
                'project_type', 'email', 'phone', 'status_lead', 'meeting_status',
                'scheduled_at', 'readed', 'created_at', 'deleted_at',
            ])
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findByUuid(string $uuid): ?AppointmentEloquentModel
    {
        return AppointmentEloquentModel::withTrashed()
            ->where('uuid', $uuid)
            ->first();
    }

    public function findActiveByEmail(string $email): ?AppointmentEloquentModel
    {
        return AppointmentEloquentModel::query()
            ->where('email', $email)
            ->first();
    }

    public function hasConflict(CarbonInterface $scheduledAt, ?string $excludeUuid = null): bool
    {
        return AppointmentEloquentModel::query()
            ->where('scheduled_at', $scheduledAt->format('Y-m-d H:i:s'))
            // NULL meeting_status (pending, not yet confirmed) still counts as a
            // conflict — only an explicitly Cancelled row frees up the slot.
            ->where(fn ($q) => $q->whereNull('meeting_status')->orWhere('meeting_status', '!=', MeetingStatus::Cancelled->value))
            ->when($excludeUuid !== null, fn ($q) => $q->where('uuid', '!=', $excludeUuid))
            ->exists();
    }

    public function create(array $attributes): AppointmentEloquentModel
    {
        return AppointmentEloquentModel::query()->create($attributes);
    }

    public function update(AppointmentEloquentModel $appointment, array $attributes): AppointmentEloquentModel
    {
        $appointment->update($attributes);

        return $appointment->refresh();
    }

    public function markAsRead(string $uuid): bool
    {
        $appointment = AppointmentEloquentModel::query()->where('uuid', $uuid)->first();

        if ($appointment === null) {
            return false;
        }

        $appointment->readed = true;

        return $appointment->save();
    }

    public function softDelete(string $uuid): bool
    {
        return (bool) AppointmentEloquentModel::query()->where('uuid', $uuid)->delete();
    }

    public function restore(string $uuid): bool
    {
        return (bool) AppointmentEloquentModel::onlyTrashed()->where('uuid', $uuid)->restore();
    }
}
