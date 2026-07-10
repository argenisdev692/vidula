<?php

declare(strict_types=1);

namespace Modules\Availability\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Availability\Application\DTOs\AvailabilityRuleFilterData;
use Modules\Availability\Application\Queries\GetAvailabilityRuleHandler;
use Modules\Availability\Application\Queries\ListAvailabilityRulesHandler;

/**
 * @group Availability Rules
 *
 * API endpoints for the weekly availability template. Secondary documentation
 * surface for Sanctum-authenticated clients. The primary UI remains Inertia/web.
 */
final readonly class AvailabilityRuleApiController
{
    /**
     * List availability rules.
     *
     * Returns a paginated list of weekly availability rules.
     *
     * @authenticated
     *
     * @queryParam per_page int Page size. Example: 15
     *
     * @response 403 {"message":"This action is unauthorized."}
     */
    public function index(Request $request, ListAvailabilityRulesHandler $list): JsonResponse
    {
        $filters = AvailabilityRuleFilterData::validateAndCreate($request);

        return response()->json($list->handle($filters, (int) $request->integer('per_page', 15)));
    }

    /**
     * Show an availability rule.
     *
     * Returns a single weekly availability rule by UUID.
     *
     * @authenticated
     *
     * @urlParam uuid string required The availability rule UUID. Example: 00000000-0000-0000-0000-000000000000
     *
     * @response 403 {"message":"This action is unauthorized."}
     * @response 404 {"message":"Not found."}
     */
    public function show(string $uuid, GetAvailabilityRuleHandler $get): JsonResponse
    {
        return response()->json(['data' => $get->handle($uuid)]);
    }
}
