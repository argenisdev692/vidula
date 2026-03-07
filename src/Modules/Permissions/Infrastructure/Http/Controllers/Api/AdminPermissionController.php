<?php

declare(strict_types=1);

namespace Modules\Permissions\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Modules\Permissions\Application\Commands\CreatePermission\CreatePermissionCommand;
use Modules\Permissions\Application\Commands\CreatePermission\CreatePermissionHandler;
use Modules\Permissions\Application\Commands\DeletePermission\DeletePermissionCommand;
use Modules\Permissions\Application\Commands\DeletePermission\DeletePermissionHandler;
use Modules\Permissions\Application\Commands\UpdatePermission\UpdatePermissionCommand;
use Modules\Permissions\Application\Commands\UpdatePermission\UpdatePermissionHandler;
use Modules\Permissions\Application\DTOs\CreatePermissionDTO;
use Modules\Permissions\Application\DTOs\PermissionFilterDTO;
use Modules\Permissions\Application\DTOs\UpdatePermissionDTO;
use Modules\Permissions\Application\Queries\GetPermission\GetPermissionHandler;
use Modules\Permissions\Application\Queries\GetPermission\GetPermissionQuery;
use Modules\Permissions\Application\Queries\ListPermissions\ListPermissionsHandler;
use Modules\Permissions\Application\Queries\ListPermissions\ListPermissionsQuery;
use Modules\Permissions\Infrastructure\Http\Requests\CreatePermissionRequest;
use Modules\Permissions\Infrastructure\Http\Requests\PermissionFilterRequest;
use Modules\Permissions\Infrastructure\Http\Requests\UpdatePermissionRequest;
use Modules\Permissions\Infrastructure\Http\Resources\PermissionResource;

final class AdminPermissionController
{
    public function __construct(
        private readonly CreatePermissionHandler $createHandler,
        private readonly UpdatePermissionHandler $updateHandler,
        private readonly DeletePermissionHandler $deleteHandler,
        private readonly ListPermissionsHandler $listHandler,
        private readonly GetPermissionHandler $getHandler,
    ) {
    }

    public function index(PermissionFilterRequest $request): JsonResponse
    {
        $filters = PermissionFilterDTO::from($request->validated());
        $result = $this->listHandler->handle(new ListPermissionsQuery($filters));

        return response()->json($result);
    }

    public function show(string $uuid): JsonResponse
    {
        $permission = $this->getHandler->handle(new GetPermissionQuery($uuid));

        return response()->json([
            'data' => new PermissionResource($permission),
        ]);
    }

    public function store(CreatePermissionRequest $request): JsonResponse
    {
        $dto = CreatePermissionDTO::from($request->validated());
        $permission = $this->createHandler->handle(new CreatePermissionCommand($dto));

        return response()->json([
            'data' => new PermissionResource($permission),
        ], 201);
    }

    public function update(UpdatePermissionRequest $request, string $uuid): JsonResponse
    {
        $dto = UpdatePermissionDTO::from($request->validated());
        $permission = $this->updateHandler->handle(new UpdatePermissionCommand($uuid, $dto));

        return response()->json([
            'data' => new PermissionResource($permission),
        ]);
    }

    public function destroy(string $uuid): JsonResponse
    {
        $this->deleteHandler->handle(new DeletePermissionCommand($uuid));

        return response()->json(null, 204);
    }
}
