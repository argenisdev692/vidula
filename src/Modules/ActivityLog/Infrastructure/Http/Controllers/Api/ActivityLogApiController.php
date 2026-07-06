<?php

declare(strict_types=1);

namespace Modules\ActivityLog\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ActivityLog\Application\DTOs\ActivityLogFilterData;
use Modules\ActivityLog\Application\Queries\GetActivityLogHandler;
use Modules\ActivityLog\Application\Queries\ListActivityLogsHandler;

/**
 * @group Activity Log
 *
 * Read-only audit trail for Sanctum-authenticated (mobile) clients. Reuses the
 * exact same query handlers as the web/Inertia controller. Authorization is
 * checked on the model (`hasPermissionTo`) rather than the `permission:`
 * middleware, which is guard-safe under the `sanctum` guard.
 */
final readonly class ActivityLogApiController
{
    /**
     * List activity log entries
     *
     * Returns a paginated, filtered list of audit-trail entries, newest first.
     *
     * @authenticated
     *
     * @queryParam search string Free-text match on description / event / subject. Example: login
     * @queryParam event string Exact event name. Example: created
     * @queryParam causer_id string Filter by the actor id. Example: 42
     * @queryParam date_from date Inclusive lower bound (Y-m-d). Example: 2026-06-01
     * @queryParam date_to date Inclusive upper bound (Y-m-d). Example: 2026-06-30
     * @queryParam sort_dir string Sort direction on created_at: asc or desc. Example: desc
     * @queryParam per_page int Page size (default 15). Example: 25
     *
     * @response 403 {"message": "This action is unauthorized."}
     */
    public function index(Request $request, ListActivityLogsHandler $list): JsonResponse
    {
        abort_unless((bool) $request->user()?->hasPermissionTo('VIEW_ANY_ACTIVITY_LOGS'), 403);

        $filters = ActivityLogFilterData::validateAndCreate($request);
        $sortDir = $request->string('sort_dir', 'desc') === 'asc' ? 'asc' : 'desc';

        return response()->json(
            $list->handle($filters, (int) $request->integer('per_page', 15), $sortDir)
        );
    }

    /**
     * Show an activity log entry
     *
     * Returns a single audit-trail entry with its full property and
     * attribute-change payloads.
     *
     * @authenticated
     *
     * @urlParam id int required The activity log id. Example: 128
     *
     * @response 404 {"message": "Not found."}
     */
    public function show(Request $request, string $id, GetActivityLogHandler $get): JsonResponse
    {
        abort_unless((bool) $request->user()?->hasPermissionTo('VIEW_ACTIVITY_LOGS'), 403);

        return response()->json(['data' => $get->handle((int) $id)]);
    }
}
