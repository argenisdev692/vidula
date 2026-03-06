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
use Modules\Users\Infrastructure\Http\Requests\CreateUserRequest;
use Modules\Users\Infrastructure\Http\Requests\UpdateUserRequest;
use Modules\Users\Infrastructure\Http\Requests\UserFilterRequest;
use Modules\Users\Infrastructure\Http\Resources\UserResource;

/**
 * AdminUserController — Full CRUD Web-JSON API for super-admin user management.
 *
 * @OA\Tag(name="Admin Users", description="Super-admin user management (web session)")
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

    /**
     * @OA\Get(
     *     path="/users/data/admin",
     *     tags={"Admin Users"},
     *     summary="List users (paginated, filtered)",
     *     @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="string", enum={"active","suspended","banned","deleted","pending_setup"})),
     *     @OA\Response(response=200, description="Paginated user list")
     * )
     */
    public function index(UserFilterRequest $request): JsonResponse
    {
        $filters = UserFilterDTO::from($request->validated());
        $result = $this->listHandler->handle(new ListUsersQuery($filters));

        return response()->json($result);
    }

    /**
     * @OA\Get(
     *     path="/users/data/admin/{uuid}",
     *     tags={"Admin Users"},
     *     summary="Get single user",
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="User details"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function show(string $uuid): JsonResponse
    {
        $user = $this->getHandler->handle(new GetUserQuery($uuid));

        return response()->json([
            'data' => new UserResource($user),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/users/data/admin",
     *     tags={"Admin Users"},
     *     summary="Create user",
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/CreateUserDTO")),
     *     @OA\Response(response=201, description="User created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(CreateUserRequest $request): JsonResponse
    {
        $dto = CreateUserDTO::from($request->validated());
        $user = $this->createHandler->handle(new CreateUserCommand($dto));

        return response()->json([
            'data' => new UserResource($user),
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/users/data/admin/{uuid}",
     *     tags={"Admin Users"},
     *     summary="Update user",
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/UpdateUserDTO")),
     *     @OA\Response(response=200, description="User updated"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function update(UpdateUserRequest $request, string $uuid): JsonResponse
    {
        $dto = UpdateUserDTO::from($request->validated());
        $user = $this->updateHandler->handle(new UpdateUserCommand($uuid, $dto));

        return response()->json([
            'data' => new UserResource($user),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/users/data/admin/{uuid}",
     *     tags={"Admin Users"},
     *     summary="Soft-delete user",
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=204, description="User deleted"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function destroy(string $uuid): JsonResponse
    {
        $this->deleteHandler->handle(new DeleteUserCommand($uuid));

        return response()->json(null, 204);
    }

    /**
     * @OA\Post(
     *     path="/users/data/admin/bulk-delete",
     *     tags={"Admin Users"},
     *     summary="Bulk soft-delete users",
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="uuids", type="array", @OA\Items(type="string", format="uuid"))
     *     )),
     *     @OA\Response(response=204, description="Users deleted")
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/users/data/admin/{uuid}/suspend",
     *     tags={"Admin Users"},
     *     summary="Suspend user",
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="User suspended")
     * )
     */
    public function suspend(string $uuid): JsonResponse
    {
        $this->suspendHandler->handle(new SuspendUserCommand($uuid));

        return response()->json(['message' => 'User suspended successfully.']);
    }

    /**
     * @OA\Post(
     *     path="/users/data/admin/{uuid}/activate",
     *     tags={"Admin Users"},
     *     summary="Activate user",
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="User activated")
     * )
     */
    public function activate(string $uuid): JsonResponse
    {
        $this->activateHandler->handle(new ActivateUserCommand($uuid));

        return response()->json(['message' => 'User activated successfully.']);
    }

    /**
     * @OA\Patch(
     *     path="/users/data/admin/{uuid}/restore",
     *     tags={"Admin Users"},
     *     summary="Restore soft-deleted user",
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="User restored")
     * )
     */
    public function restore(string $uuid): JsonResponse
    {
        $this->restoreHandler->handle(new RestoreUserCommand($uuid));

        return response()->json(['message' => 'User restored successfully.']);
    }
}
