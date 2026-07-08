<?php

declare(strict_types=1);

namespace Modules\Authorization\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Authorization\Application\DTOs\PermissionFilterData;
use Modules\Authorization\Application\Queries\GetPermissionHandler;
use Modules\Authorization\Application\Queries\ListPermissionsHandler;

/**
 * @group Permissions
 *
 * API endpoints for permission lookup. Secondary documentation surface for
 * Sanctum-authenticated clients.
 */
final readonly class PermissionApiController
{
    /**
     * List permissions.
     *
     * Returns a paginated list of permissions.
     *
     * @authenticated
     *
     * @queryParam per_page int Page size. Example: 15
     *
     * @response 403 {"message":"This action is unauthorized."}
     */
    public function index(Request $request, ListPermissionsHandler $list): JsonResponse
    {
        abort_unless((bool) $request->user()?->hasPermissionTo('VIEW_ANY_PERMISSIONS'), 403);

        $filters = PermissionFilterData::validateAndCreate($request);

        return response()->json($list->handle($filters, (int) $request->integer('per_page', 15)));
    }

    /**
     * Show a permission.
     *
     * Returns a single permission by UUID.
     *
     * @authenticated
     *
     * @urlParam uuid string required The permission UUID. Example: 00000000-0000-0000-0000-000000000000
     *
     * @response 403 {"message":"This action is unauthorized."}
     * @response 404 {"message":"Not found."}
     */
    public function show(Request $request, string $uuid, GetPermissionHandler $get): JsonResponse
    {
        abort_unless((bool) $request->user()?->hasPermissionTo('VIEW_PERMISSIONS'), 403);

        return response()->json(['data' => $get->handle($uuid)]);
    }
}
