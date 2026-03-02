<?php

declare(strict_types=1);

namespace Modules\Users\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Users\Application\Commands\ActivateUser\ActivateUserCommand;
use Modules\Users\Application\Commands\ActivateUser\ActivateUserHandler;
use Modules\Users\Application\Commands\CreateUser\CreateUserCommand;
use Modules\Users\Application\Commands\CreateUser\CreateUserHandler;
use Modules\Users\Application\Commands\DeleteUser\DeleteUserCommand;
use Modules\Users\Application\Commands\DeleteUser\DeleteUserHandler;
use Modules\Users\Application\Commands\SuspendUser\SuspendUserCommand;
use Modules\Users\Application\Commands\SuspendUser\SuspendUserHandler;
use Modules\Users\Application\Commands\UpdateUser\UpdateUserCommand;
use Modules\Users\Application\Commands\UpdateUser\UpdateUserHandler;
use Modules\Users\Application\Commands\RestoreUser\RestoreUserCommand;
use Modules\Users\Application\Commands\RestoreUser\RestoreUserHandler;
use Modules\Users\Application\DTOs\CreateUserDTO;
use Modules\Users\Application\DTOs\UpdateUserDTO;
use Modules\Users\Application\DTOs\UserFilterDTO;
use Modules\Users\Application\Queries\GetUser\GetUserHandler;
use Modules\Users\Application\Queries\GetUser\GetUserQuery;
use Modules\Users\Application\Queries\ListUsers\ListUsersHandler;
use Modules\Users\Application\Queries\ListUsers\ListUsersQuery;
use Modules\Users\Domain\Ports\UserRepositoryPort;
use Modules\Users\Infrastructure\Http\Requests\CreateUserRequest;
use Modules\Users\Infrastructure\Http\Requests\UpdateUserRequest;
use Modules\Users\Infrastructure\Http\Requests\UserFilterRequest;
use Modules\Users\Infrastructure\Http\Resources\UserResource;

/**
 * AdminUserController — Full CRUD API for super-admin user management.
 */
final class AdminUserController
{
    public function __construct(
        private readonly CreateUserHandler $createHandler,
        private readonly UpdateUserHandler $updateHandler,
        private readonly DeleteUserHandler $deleteHandler,
        private readonly SuspendUserHandler $suspendHandler,
        private readonly ActivateUserHandler $activateHandler,
        private readonly RestoreUserHandler $restoreHandler,
        private readonly ListUsersHandler $listHandler,
        private readonly GetUserHandler $getHandler,
    ) {
    }

    public function index(UserFilterRequest $request): JsonResponse
    {
        $filters = UserFilterDTO::from($request->validated());
        $result = $this->listHandler->handle(new ListUsersQuery($filters));

        return response()->json($result);
    }

    public function show(string $uuid): JsonResponse
    {
        $user = $this->getHandler->handle(new GetUserQuery($uuid));

        return response()->json([
            'data' => new UserResource($user),
        ]);
    }

    public function store(CreateUserRequest $request): JsonResponse
    {
        $dto = CreateUserDTO::from($request->validated());
        $user = $this->createHandler->handle(new CreateUserCommand($dto));

        return response()->json([
            'data' => new UserResource($user),
        ], 201);
    }

    public function update(UpdateUserRequest $request, string $uuid): JsonResponse
    {
        $dto = UpdateUserDTO::from($request->validated());
        $user = $this->updateHandler->handle(new UpdateUserCommand($uuid, $dto));

        return response()->json([
            'data' => new UserResource($user),
        ]);
    }

    public function destroy(string $uuid): JsonResponse
    {
        $this->deleteHandler->handle(new DeleteUserCommand($uuid));

        return response()->json(null, 204);
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'uuids' => ['required', 'array'],
            'uuids.*' => ['required', 'string', 'uuid'],
        ]);

        foreach ($validated['uuids'] as $uuid) {
            $this->deleteHandler->handle(new DeleteUserCommand($uuid));
        }

        return response()->json(null, 204);
    }

    public function suspend(string $uuid): JsonResponse
    {
        $this->suspendHandler->handle(new SuspendUserCommand($uuid));

        return response()->json(['message' => 'User suspended successfully.']);
    }

    public function activate(string $uuid): JsonResponse
    {
        $this->activateHandler->handle(new ActivateUserCommand($uuid));

        return response()->json(['message' => 'User activated successfully.']);
    }

    public function restore(string $uuid): JsonResponse
    {
        $this->restoreHandler->handle(new RestoreUserCommand($uuid));

        return response()->json(['message' => 'User restored successfully.']);
    }
}
