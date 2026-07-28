<?php

declare(strict_types=1);

namespace Modules\Meeting\Infrastructure\Persistence\Repositories;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Modules\Meeting\Application\DTOs\MeetingFilterData;
use Modules\Meeting\Domain\Ports\MeetingRepositoryPort;
use Modules\Meeting\Infrastructure\Persistence\Eloquent\Models\MeetingEloquentModel;
use Shared\Infrastructure\Persistence\Concerns\BulkSoftDeletesByUuid;

/**
 * Eloquent adapter for {@see MeetingRepositoryPort}. Bulk soft-delete /
 * restore reuse the Shared {@see BulkSoftDeletesByUuid} trait (DRY, mirrors
 * Appointment/Availability/ContactSupport).
 */
final readonly class EloquentMeetingRepository implements MeetingRepositoryPort
{
    use BulkSoftDeletesByUuid;

    /**
     * @return class-string<MeetingEloquentModel>
     */
    protected function model(): string
    {
        return MeetingEloquentModel::class;
    }

    public function paginate(MeetingFilterData $filters, int $perPage): LengthAwarePaginator
    {
        return $this->filteredQuery($filters)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function lazyForExport(MeetingFilterData $filters): LazyCollection
    {
        return $this->filteredQuery($filters)->lazy();
    }

    public function findByUuid(string $uuid): ?MeetingEloquentModel
    {
        // `attendable` is deliberately NOT eager-loaded here: it would pull the
        // full target row (a User row includes `two_factor_secret` etc., which
        // is NOT in `App\Models\User::$hidden`) into every cached Show/Edit
        // response. `MeetingController::edit()` resolves minimal attendee
        // labels separately via `AttendeeOptionMapper` (column-scoped queries).
        return MeetingEloquentModel::withTrashed()
            ->with(['organizer:id,uuid,first_name,last_name', 'attendees'])
            ->where('uuid', $uuid)
            ->first();
    }

    public function between(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return MeetingEloquentModel::query()
            ->where('starts_at', '<=', $to)
            ->where('ends_at', '>=', $from)
            ->get();
    }

    public function create(array $attributes): MeetingEloquentModel
    {
        return MeetingEloquentModel::query()->create($attributes);
    }

    public function update(MeetingEloquentModel $meeting, array $attributes): MeetingEloquentModel
    {
        $meeting->update($attributes);

        return $meeting->refresh();
    }

    public function syncAttendees(MeetingEloquentModel $meeting, array $attendeeRows): void
    {
        $meeting->attendees()->delete();

        if ($attendeeRows === []) {
            return;
        }

        $meeting->attendees()->createMany($attendeeRows);
    }

    public function softDelete(string $uuid): bool
    {
        return (bool) MeetingEloquentModel::query()->where('uuid', $uuid)->delete();
    }

    public function restore(string $uuid): bool
    {
        return (bool) MeetingEloquentModel::onlyTrashed()->where('uuid', $uuid)->restore();
    }

    public function ownedUuidsAmong(array $uuids, int $organizerId, bool $onlyTrashed = false): array
    {
        if ($uuids === []) {
            return [];
        }

        $query = $onlyTrashed
            ? MeetingEloquentModel::onlyTrashed()
            : MeetingEloquentModel::query();

        /** @var list<string> $owned */
        $owned = $query
            ->where('organizer_id', $organizerId)
            ->whereIn('uuid', $uuids)
            ->pluck('uuid')
            ->all();

        return $owned;
    }

    /**
     * Shared list/export query builder (BACKEND-PHP §4.1 + §5.2 / §8) —
     * soft-delete status, filters, eager loads, and client sort.
     *
     * @return Builder<MeetingEloquentModel>
     */
    private function filteredQuery(MeetingFilterData $filters): Builder
    {
        return MeetingEloquentModel::query()
            ->when($filters->status === 'suspended', fn ($q) => $q->onlyTrashed())
            ->applyFilters($filters)
            ->withCount('attendees')
            ->with('organizer:id,uuid,first_name,last_name')
            ->orderBy($filters->resolvedSortField(), $filters->resolvedSortDirection());
    }
}
