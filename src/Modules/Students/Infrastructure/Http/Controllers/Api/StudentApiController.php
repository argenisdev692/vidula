<?php

declare(strict_types=1);

namespace Modules\Students\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Students\Application\DTOs\StudentFilterData;
use Modules\Students\Application\Queries\GetStudentHandler;
use Modules\Students\Application\Queries\ListStudentsHandler;

/**
 * Sanctum-authenticated Students API (secondary). Primary UI remains Inertia/web.
 * Scramble documents via return types + `auth:sanctum` — no manual annotations.
 */
final readonly class StudentApiController
{
    /**
     * List students.
     *
     * Returns a paginated, filterable LMS student list. `per_page` is capped at
     * 100 to bound resource consumption (OWASP API4).
     */
    public function index(Request $request, ListStudentsHandler $list): JsonResponse
    {
        abort_unless((bool) $request->user()?->hasPermissionTo('VIEW_ANY_STUDENTS'), 403);

        $filters = StudentFilterData::validateAndCreate($request);

        return response()->json($list->handle($filters, min(max($request->integer('per_page', 15), 1), 100)));
    }

    /**
     * Show a student.
     *
     * Returns a single LMS student by UUID.
     */
    public function show(Request $request, string $uuid, GetStudentHandler $get): JsonResponse
    {
        abort_unless((bool) $request->user()?->hasPermissionTo('VIEW_STUDENTS'), 403);

        return response()->json(['data' => $get->handle($uuid)]);
    }
}
