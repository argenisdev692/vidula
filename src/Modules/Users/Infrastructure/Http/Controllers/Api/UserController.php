<?php

declare(strict_types=1);

namespace Modules\Users\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Users\Application\Commands\CreateUser\CreateUserCommand;
use Modules\Users\Application\Commands\CreateUser\CreateUserHandler;
use Modules\Users\Application\Commands\DeleteUser\DeleteUserCommand;
use Modules\Users\Application\Commands\DeleteUser\DeleteUserHandler;
use Modules\Users\Application\Commands\UpdateUser\UpdateUserCommand;
use Modules\Users\Application\Commands\UpdateUser\UpdateUserHandler;
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
 * UserController — Full CRUD API for admin user management.
 *
 * Only orchestration — no business logic here.
 */
final class UserController
{
    public function __construct(
        private readonly CreateUserHandler $createHandler,
        private readonly UpdateUserHandler $updateHandler,
        private readonly DeleteUserHandler $deleteHandler,
        private readonly ListUsersHandler $listHandler,
        private readonly GetUserHandler $getHandler,
        private readonly UserRepositoryPort $repository,
    ) {
    }

    /**
     * GET /api/users — Paginated list with filters.
     */
    public function index(UserFilterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $filters = new UserFilterDTO(
            page: (int) ($validated['page'] ?? 1),
            perPage: (int) ($validated['per_page'] ?? 15),
            search: $validated['search'] ?? null,
            status: $validated['status'] ?? null,
            dateFrom: $validated['date_from'] ?? null,
            dateTo: $validated['date_to'] ?? null,
            sortBy: $validated['sort_by'] ?? 'created_at',
            sortDir: $validated['sort_dir'] ?? 'desc',
        );

        $result = $this->listHandler->handle(new ListUsersQuery($filters));

        return response()->json([
            'data' => UserResource::collection(collect($result['data']))->resolve(),
            'meta' => [
                'currentPage' => $result['currentPage'],
                'lastPage' => $result['lastPage'],
                'perPage' => $result['perPage'],
                'total' => $result['total'],
            ],
        ]);
    }

    /**
     * GET /api/users/{uuid} — Single user detail.
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
     */
    public function store(CreateUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $dto = new CreateUserDTO(
            name: $validated['name'],
            email: $validated['email'],
            lastName: $validated['last_name'] ?? null,
            username: $validated['username'] ?? null,
            phone: $validated['phone'] ?? null,
            address: $validated['address'] ?? null,
            city: $validated['city'] ?? null,
            state: $validated['state'] ?? null,
            country: $validated['country'] ?? null,
            zipCode: $validated['zip_code'] ?? null,
            password: $validated['password'] ?? null,
        );

        $user = $this->createHandler->handle(new CreateUserCommand($dto));

        return response()->json([
            'data' => new UserResource($user),
        ], 201);
    }

    /**
     * PUT /api/users/{uuid} — Update an existing user.
     */
    public function update(UpdateUserRequest $request, string $uuid): JsonResponse
    {
        $validated = $request->validated();

        $dto = new UpdateUserDTO(
            name: $validated['name'] ?? null,
            lastName: $validated['last_name'] ?? null,
            email: $validated['email'] ?? null,
            username: $validated['username'] ?? null,
            phone: $validated['phone'] ?? null,
            address: $validated['address'] ?? null,
            city: $validated['city'] ?? null,
            state: $validated['state'] ?? null,
            country: $validated['country'] ?? null,
            zipCode: $validated['zip_code'] ?? null,
        );

        $user = $this->updateHandler->handle(new UpdateUserCommand($uuid, $dto));

        return response()->json([
            'data' => new UserResource($user),
        ]);
    }

    /**
     * DELETE /api/users/{uuid} — Soft delete a user.
     */
    public function destroy(string $uuid): JsonResponse
    {
        $this->deleteHandler->handle(new DeleteUserCommand($uuid));

        return response()->json(null, 204);
    }

    /**
     * PATCH /api/users/{uuid}/restore — Restore a soft-deleted user.
     */
    public function restore(string $uuid): JsonResponse
    {
        $this->repository->restore($uuid);

        return response()->json(['message' => 'User restored successfully.']);
    }
}
