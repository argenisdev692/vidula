<?php

declare(strict_types=1);

namespace Modules\Roles\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Modules\Roles\Application\Commands\CreateRole\CreateRoleCommand;
use Modules\Roles\Application\Commands\CreateRole\CreateRoleHandler;
use Modules\Roles\Application\Commands\DeleteRole\DeleteRoleCommand;
use Modules\Roles\Application\Commands\DeleteRole\DeleteRoleHandler;
use Modules\Roles\Application\Commands\UpdateRole\UpdateRoleCommand;
use Modules\Roles\Application\Commands\UpdateRole\UpdateRoleHandler;
use Modules\Roles\Application\DTOs\CreateRoleDTO;
use Modules\Roles\Application\DTOs\RoleFilterDTO;
use Modules\Roles\Application\DTOs\UpdateRoleDTO;
use Modules\Roles\Application\Queries\GetRole\GetRoleHandler;
use Modules\Roles\Application\Queries\GetRole\GetRoleQuery;
use Modules\Roles\Application\Queries\ListRoles\ListRolesHandler;
use Modules\Roles\Application\Queries\ListRoles\ListRolesQuery;
use Modules\Roles\Domain\Exceptions\ProtectedRoleException;
use Modules\Roles\Infrastructure\Http\Requests\CreateRoleRequest;
use Modules\Roles\Infrastructure\Http\Requests\RoleFilterRequest;
use Modules\Roles\Infrastructure\Http\Requests\UpdateRoleRequest;
use Modules\Roles\Infrastructure\Http\Resources\RoleResource;

final class AdminRoleController
{
    public function __construct(
        private readonly CreateRoleHandler $createHandler,
        private readonly UpdateRoleHandler $updateHandler,
        private readonly DeleteRoleHandler $deleteHandler,
        private readonly ListRolesHandler $listHandler,
        private readonly GetRoleHandler $getHandler,
    ) {
    }

    public function index(RoleFilterRequest $request): JsonResponse
    {
        $filters = RoleFilterDTO::from($request->validated());
        $result = $this->listHandler->handle(new ListRolesQuery($filters));

        return response()->json($result);
    }

    public function show(string $uuid): JsonResponse
    {
        $role = $this->getHandler->handle(new GetRoleQuery($uuid));

        return response()->json([
            'data' => new RoleResource($role),
        ]);
    }

    public function store(CreateRoleRequest $request): JsonResponse
    {
        $dto = CreateRoleDTO::from($request->validated());
        $role = $this->createHandler->handle(new CreateRoleCommand($dto));

        return response()->json([
            'data' => new RoleResource($role),
        ], 201);
    }

    public function update(UpdateRoleRequest $request, string $uuid): JsonResponse
    {
        $dto = UpdateRoleDTO::from($request->validated());
        $role = $this->updateHandler->handle(new UpdateRoleCommand($uuid, $dto));

        return response()->json([
            'data' => new RoleResource($role),
        ]);
    }

    public function destroy(string $uuid): JsonResponse
    {
        try {
            $this->deleteHandler->handle(new DeleteRoleCommand($uuid));
        } catch (ProtectedRoleException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json(null, 204);
    }
}
