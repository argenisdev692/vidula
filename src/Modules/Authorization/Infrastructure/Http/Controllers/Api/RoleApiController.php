<?php

declare(strict_types=1);

namespace Modules\Authorization\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Authorization\Application\DTOs\RoleFilterData;
use Modules\Authorization\Application\Queries\GetRoleHandler;
use Modules\Authorization\Application\Queries\ListRolesHandler;

/**
 * @group Roles
 *
 * API endpoints for role lookup. Secondary documentation surface for
 * Sanctum-authenticated clients. The primary UI remains Inertia/web.
 */
final readonly class RoleApiController
{
    /**
     * List roles.
     *
     * Returns a paginated list of roles.
     *
     * @authenticated
     *
     * @queryParam per_page int Page size. Example: 15
     *
     * @response 403 {"message":"This action is unauthorized."}
     */
    public function index(Request $request, ListRolesHandler $list): JsonResponse
    {
        abort_unless((bool) $request->user()?->hasPermissionTo('VIEW_ANY_ROLES'), 403);

        $filters = RoleFilterData::validateAndCreate($request);

        return response()->json($list->handle($filters, (int) $request->integer('per_page', 15)));
    }

    /**
     * Show a role.
     *
     * Returns a single role (with its permissions) by UUID.
     *
     * @authenticated
     *
     * @urlParam uuid string required The role UUID. Example: 00000000-0000-0000-0000-000000000000
     *
     * @response 403 {"message":"This action is unauthorized."}
     * @response 404 {"message":"Not found."}
     */
    public function show(Request $request, string $uuid, GetRoleHandler $get): JsonResponse
    {
        abort_unless((bool) $request->user()?->hasPermissionTo('VIEW_ROLES'), 403);

        return response()->json(['data' => $get->handle($uuid)]);
    }
}
