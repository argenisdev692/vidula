<?php

declare(strict_types=1);

namespace Modules\Enrollments\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Enrollments\Application\DTOs\EnrollmentFilterData;
use Modules\Enrollments\Application\Queries\GetAttendanceSheetHandler;
use Modules\Enrollments\Application\Queries\GetEnrollmentHandler;
use Modules\Enrollments\Application\Queries\ListEnrollmentsHandler;

/**
 * Sanctum-authenticated Enrollments API (secondary). Primary UI remains Inertia/web.
 * Scramble documents via return types + `auth:sanctum` — no manual annotations.
 */
final readonly class EnrollmentApiController
{
    /**
     * List enrollments.
     *
     * Returns a paginated, filterable classroom enrollment list. `per_page` is
     * capped at 100 to bound resource consumption (OWASP API4).
     */
    public function index(Request $request, ListEnrollmentsHandler $list): JsonResponse
    {
        abort_unless((bool) $request->user()?->hasPermissionTo('VIEW_ANY_ENROLLMENTS'), 403);

        $filters = EnrollmentFilterData::validateAndCreate($request);

        return response()->json($list->handle($filters, min(max($request->integer('per_page', 15), 1), 100)));
    }

    /**
     * Show an enrollment.
     *
     * Returns a single classroom enrollment by UUID, including attendances.
     */
    public function show(Request $request, string $uuid, GetEnrollmentHandler $get): JsonResponse
    {
        abort_unless((bool) $request->user()?->hasPermissionTo('VIEW_ENROLLMENTS'), 403);

        return response()->json(['data' => $get->handle($uuid)]);
    }

    /**
     * Attendance sheet for a classroom.
     *
     * Returns sessions, enrollments, and attendance marks for Lista Asistencia.
     */
    public function attendanceSheet(
        Request $request,
        string $classroomUuid,
        GetAttendanceSheetHandler $get,
    ): JsonResponse {
        abort_unless((bool) $request->user()?->hasPermissionTo('VIEW_ANY_ENROLLMENTS'), 403);

        return response()->json($get->handle($classroomUuid));
    }
}
