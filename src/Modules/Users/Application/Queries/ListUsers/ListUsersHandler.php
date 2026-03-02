<?php

declare(strict_types=1);

namespace Modules\Users\Application\Queries\ListUsers;

use Modules\Users\Application\Queries\ReadModels\UserListReadModel;
use Modules\Users\Domain\Ports\UserRepositoryPort;
use Illuminate\Support\Facades\Cache;

final readonly class ListUsersHandler
{
    public function __construct(
        private UserRepositoryPort $userRepository,
    ) {
    }

    /**
     * @return array{data: list<UserListReadModel>, total: int, perPage: int, currentPage: int, lastPage: int}
     */
    public function handle(ListUsersQuery $query): array
    {
        $filters = $query->filters;
        $cacheKey = "users_list_" . md5(serialize($filters->toArray()));
        $ttl = 60 * 15; // 15 minutes

        try {
            // Try to use cache tags (requires Redis/Memcached)
            return Cache::tags(['users_list'])->remember($cacheKey, $ttl, function () use ($filters) {
                return $this->fetchAndMapUsers($filters);
            });
        } catch (\Exception $e) {
            // Fallback to regular cache if tags not supported
            return Cache::remember($cacheKey, $ttl, function () use ($filters) {
                return $this->fetchAndMapUsers($filters);
            });
        }
    }

    /**
     * @return array{data: list<UserListReadModel>, total: int, perPage: int, currentPage: int, lastPage: int}
     */
    private function fetchAndMapUsers($filters): array
    {
        $result = $this->userRepository->findAllPaginated(
            filters: $filters->toArray(),
            page: $filters->page,
            perPage: $filters->perPage,
        );

        // Transform users using pipe operator
        $result['data'] = $result['data']
            |> (fn($users) => array_map(self::mapToReadModel(...), $users));

        return $result;
    }

    /**
     * Map domain User entity to UserListReadModel
     */
    private static function mapToReadModel($user): UserListReadModel
    {
        return new UserListReadModel(
            uuid: $user->uuid,
            name: $user->name ?? '',
            lastName: $user->lastName ?? '',
            fullName: $user->fullName(),
            email: $user->email ?? '',
            username: $user->username,
            phone: $user->phone,
            status: $user->status->value,
            profilePhotoPath: $user->profilePhotoPath,
            role: null, // To be implemented with RBAC service
            createdAt: $user->createdAt ?? '',
            updatedAt: $user->updatedAt ?? '',
            deletedAt: $user->deletedAt,
        );
    }
}
