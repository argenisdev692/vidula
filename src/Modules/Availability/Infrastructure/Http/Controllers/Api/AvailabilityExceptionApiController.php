<?php

declare(strict_types=1);

namespace Modules\Availability\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Availability\Application\DTOs\AvailabilityExceptionFilterData;
use Modules\Availability\Application\Queries\GetAvailabilityExceptionHandler;
use Modules\Availability\Application\Queries\ListAvailabilityExceptionsHandler;

/**
 * @group Availability Exceptions
 *
 * API endpoints for date-specific overrides (closures / forced-open days).
 * Secondary documentation surface for Sanctum-authenticated clients. The
 * primary UI remains Inertia/web.
 */
final readonly class AvailabilityExceptionApiController
{
    /**
     * List availability exceptions.
     *
     * Returns a paginated list of date exceptions.
     *
     * @authenticated
     *
     * @queryParam per_page int Page size. Example: 15
     *
     * @response 403 {"message":"This action is unauthorized."}
     */
    public function index(Request $request, ListAvailabilityExceptionsHandler $list): JsonResponse
    {
        $filters = AvailabilityExceptionFilterData::validateAndCreate($request);

        return response()->json($list->handle($filters, (int) $request->integer('per_page', 15)));
    }

    /**
     * Show an availability exception.
     *
     * Returns a single date exception by UUID.
     *
     * @authenticated
     *
     * @urlParam uuid string required The availability exception UUID. Example: 00000000-0000-0000-0000-000000000000
     *
     * @response 403 {"message":"This action is unauthorized."}
     * @response 404 {"message":"Not found."}
     */
    public function show(string $uuid, GetAvailabilityExceptionHandler $get): JsonResponse
    {
        return response()->json(['data' => $get->handle($uuid)]);
    }
}
