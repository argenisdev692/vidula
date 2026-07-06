<?php

declare(strict_types=1);

namespace Modules\ActivityLog\Application\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\ActivityLog\Application\DTOs\ActivityLogFilterData;
use Modules\ActivityLog\Infrastructure\Http\Presenters\ActivityLogPresenter;
use Spatie\Activitylog\Models\Activity;

/**
 * Paginated, filtered read over the immutable activity-log trail. Eager-loads
 * `causer` to resolve the actor label without N+1, and maps each row through
 * {@see ActivityLogPresenter} while preserving the flat paginator envelope
 * (`->through()`) the frontend `PaginatedResponse<T>` expects.
 */
final readonly class ListActivityLogsHandler
{
    /**
     * @param  'asc'|'desc'  $sortDirection  ordering on `created_at` (whitelisted by the caller)
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function handle(ActivityLogFilterData $filters, int $perPage = 15, string $sortDirection = 'desc'): LengthAwarePaginator
    {
        $direction = $sortDirection === 'asc' ? 'asc' : 'desc';

        return $filters
            ->applyTo(Activity::query()->with('causer'))
            ->orderBy('created_at', $direction)
            ->paginate($perPage)
            ->through(ActivityLogPresenter::toListItem(...));
    }
}
