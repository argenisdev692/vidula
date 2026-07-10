<?php

declare(strict_types=1);

namespace Modules\ActivityLog\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\ActivityLog\Application\DTOs\ActivityLogFilterData;
use Modules\ActivityLog\Application\Queries\GetActivityLogHandler;
use Modules\ActivityLog\Application\Queries\ListActivityLogsHandler;

/**
 * Read-only audit-trail viewer. The trail is immutable: NO create/update/delete
 * routes exist — rows are written by the app and removed only by the scheduled
 * archive+purge command. Every route is authorized via `permission:*_ACTIVITY_LOGS`
 * middleware (not roles). Controller stays thin: validate → handler → response.
 */
final readonly class ActivityLogController
{
    public function index(Request $request, ListActivityLogsHandler $list): InertiaResponse|JsonResponse
    {
        $filters = ActivityLogFilterData::validateAndCreate($request);
        $sortDir = $request->string('sort_dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $logs = $list->handle($filters, min(max($request->integer('per_page', 15), 1), 100), $sortDir);

        return $request->expectsJson()
            ? response()->json($logs)
            : Inertia::render('activity-logs/Index', ['logs' => $logs, 'filters' => $filters]);
    }

    public function show(string $activity, GetActivityLogHandler $get): InertiaResponse|JsonResponse
    {
        $log = $get->handle((int) $activity);

        return request()->expectsJson()
            ? response()->json(['data' => $log])
            : Inertia::render('activity-logs/Show', ['log' => $log]);
    }
}
