<?php

declare(strict_types=1);

namespace Modules\ActivityLog\Application\Queries;

use Modules\ActivityLog\Infrastructure\Http\Presenters\ActivityLogPresenter;
use Spatie\Activitylog\Models\Activity;

/**
 * Single activity-log entry for the read-only detail screen. Eager-loads the
 * `causer` and `subject` relations so the detail card can label both actors.
 */
final readonly class GetActivityLogHandler
{
    /**
     * @return array<string, mixed>
     */
    public function handle(int $id): array
    {
        $activity = Activity::query()
            ->with(['causer', 'subject'])
            ->findOrFail($id);

        return ActivityLogPresenter::toDetail($activity);
    }
}
