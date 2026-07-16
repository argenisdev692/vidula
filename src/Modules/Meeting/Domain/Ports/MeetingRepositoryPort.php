<?php

declare(strict_types=1);

namespace Modules\Meeting\Domain\Ports;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Meeting\Application\DTOs\MeetingFilterData;
use Modules\Meeting\Infrastructure\Persistence\Eloquent\Models\MeetingEloquentModel;

interface MeetingRepositoryPort
{
    /**
     * @return LengthAwarePaginator<int, MeetingEloquentModel>
     */
    public function paginate(MeetingFilterData $filters, int $perPage): LengthAwarePaginator;

    public function findByUuid(string $uuid): ?MeetingEloquentModel;

    /**
     * Own meetings whose range overlaps `[$from, $to]` — feeds the calendar
     * alongside the read-only Appointment overlay.
     *
     * @return Collection<int, MeetingEloquentModel>
     */
    public function between(CarbonInterface $from, CarbonInterface $to): Collection;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): MeetingEloquentModel;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(MeetingEloquentModel $meeting, array $attributes): MeetingEloquentModel;

    /**
     * Replaces the full attendee set for the meeting (delete-then-insert —
     * simplest correct semantics for a "resend the whole list" form).
     *
     * @param  array<int, array{attendable_type: string, attendable_id: int}>  $attendeeRows
     */
    public function syncAttendees(MeetingEloquentModel $meeting, array $attendeeRows): void;

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
