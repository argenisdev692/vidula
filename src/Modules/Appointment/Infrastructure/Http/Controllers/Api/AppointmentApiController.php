<?php

declare(strict_types=1);

namespace Modules\Appointment\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Appointment\Application\DTOs\AppointmentFilterData;
use Modules\Appointment\Application\Queries\GetAppointmentHandler;
use Modules\Appointment\Application\Queries\ListAppointmentsHandler;

/**
 * Read-only Sanctum-authenticated API surface. Primary UI remains
 * Inertia/web; this mirrors AvailabilityRuleApiController for external/mobile
 * clients. Documented by Scramble via return types + `auth:sanctum` detection.
 */
final readonly class AppointmentApiController
{
    /**
     * List appointments.
     *
     * Returns a paginated, filterable list of leads/appointments. `per_page` is
     * capped at 100 to bound resource consumption (OWASP API4).
     */
    public function index(Request $request, ListAppointmentsHandler $list): JsonResponse
    {
        $filters = AppointmentFilterData::validateAndCreate($request);

        return response()->json($list->handle($filters, min(max($request->integer('per_page', 15), 1), 100)));
    }

    /**
     * Show an appointment.
     *
     * Returns a single lead/appointment by UUID.
     */
    public function show(string $uuid, GetAppointmentHandler $get): JsonResponse
    {
        return response()->json(['data' => $get->handle($uuid)]);
    }
}
