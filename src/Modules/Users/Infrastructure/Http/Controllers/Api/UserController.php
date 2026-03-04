<?php

declare(strict_types=1);

namespace Modules\Users\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Modules\Users\Application\Commands\CreateUser\CreateUserCommand;
use Modules\Users\Application\Commands\CreateUser\CreateUserHandler;
use Modules\Users\Application\Commands\DeleteUser\DeleteUserCommand;
use Modules\Users\Application\Commands\DeleteUser\DeleteUserHandler;
use Modules\Users\Application\Commands\RestoreUser\RestoreUserCommand;
use Modules\Users\Application\Commands\RestoreUser\RestoreUserHandler;
use Modules\Users\Application\Commands\UpdateUser\UpdateUserCommand;
use Modules\Users\Application\Commands\UpdateUser\UpdateUserHandler;
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
 * UserController — Public API (Sanctum-authenticated) for user management.
 *
 * Only orchestration — no business logic here.
 *
 * @OA\Tag(name="Users", description="User management endpoints")
 */
final class UserController
{
    public function __construct(
        private readonly CreateUserHandler $createHandler,
        private readonly UpdateUserHandler $updateHandler,
        private readonly DeleteUserHandler $deleteHandler,
        private readonly RestoreUserHandler $restoreHandler,
        private readonly ListUsersHandler $listHandler,
        private readonly GetUserHandler $getHandler,
    ) {
    }

    /**
     * GET /api/users — Paginated list with filters.
     *
     * @OA\Get(
     *     path="/api/users/admin",
     *     tags={"Users"},
     *     summary="List users (paginated)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="string", enum={"active","suspended","banned","deleted"})),
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
     * GET /api/users/{uuid} — Single user detail.
     *
     * @OA\Get(
     *     path="/api/users/admin/{uuid}",
     *     tags={"Users"},
     *     summary="Get user by UUID",
     *     security={{"sanctum":{}}},
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
     * POST /api/users — Create a new user.
     *
     * @OA\Post(
     *     path="/api/users/admin",
     *     tags={"Users"},
     *     summary="Create a new user",
     *     security={{"sanctum":{}}},
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
     * PUT /api/users/{uuid} — Update an existing user.
     *
     * @OA\Put(
     *     path="/api/users/admin/{uuid}",
     *     tags={"Users"},
     *     summary="Update user",
     *     security={{"sanctum":{}}},
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
     * DELETE /api/users/{uuid} — Soft delete a user.
     *
     * @OA\Delete(
     *     path="/api/users/admin/{uuid}",
     *     tags={"Users"},
     *     summary="Soft-delete user",
     *     security={{"sanctum":{}}},
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
     * PATCH /api/users/{uuid}/restore — Restore a soft-deleted user.
     *
     * @OA\Patch(
     *     path="/api/users/admin/{uuid}/restore",
     *     tags={"Users"},
     *     summary="Restore soft-deleted user",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="User restored"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function restore(string $uuid): JsonResponse
    {
        $this->restoreHandler->handle(new RestoreUserCommand($uuid));

        return response()->json(['message' => 'User restored successfully.']);
    }
}
