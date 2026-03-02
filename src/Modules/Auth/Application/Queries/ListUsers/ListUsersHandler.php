<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Queries\ListUsers;

use Modules\Auth\Contracts\DTOs\UserListReadModel;
use Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserEloquentModel;
use Illuminate\Support\Facades\Cache;
use Modules\Auth\Infrastructure\Persistence\Mappers\UserMapper;

/**
 * ListUsersHandler — Handles user listing with caching, pagination and PHP 8.5 pipe operator.
 */
final readonly class ListUsersHandler
{
    #[\NoDiscard]
    public function handle(ListUsersQuery $query): array
    {
        return $query
            |> $this->buildCacheKey(...)
            |> $this->getFromCacheOrDatabase(...)
            |> $this->mapToReadModels(...);
    }

    private function buildCacheKey(ListUsersQuery $query): array
    {
        $cacheKey = sprintf(
            'users_list_%d_%d_%s_%s_%s_%s',
            $query->page,
            $query->perPage,
            $query->search ?? 'all',
            $query->emailVerified === null ? 'all' : ($query->emailVerified ? 'verified' : 'unverified'),
            $query->sortBy,
            $query->sortDirection
        );

        return ['query' => $query, 'cacheKey' => $cacheKey];
    }

    private function getFromCacheOrDatabase(array $data): array
    {
        $query = $data['query'];
        $cacheKey = $data['cacheKey'];

        // Try cache with tags first (Redis/Memcached)
        try {
            $result = Cache::tags(['users_list'])->remember($cacheKey, 600, function () use ($query) {
                return $this->fetchData($query);
            });
        } catch (\Exception $e) {
            // Fallback to regular cache if tags not supported
            $result = Cache::remember($cacheKey, 600, function () use ($query) {
                return $this->fetchData($query);
            });
        }

        return ['result' => $result];
    }

    private function fetchData(ListUsersQuery $query): array
    {
        $queryBuilder = UserEloquentModel::query();

        // Apply search filter
        if ($query->search) {
            $queryBuilder->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query->search}%")
                  ->orWhere('email', 'like', "%{$query->search}%")
                  ->orWhere('username', 'like', "%{$query->search}%");
            });
        }

        // Apply email verified filter
        if ($query->emailVerified !== null) {
            if ($query->emailVerified) {
                $queryBuilder->whereNotNull('email_verified_at');
            } else {
                $queryBuilder->whereNull('email_verified_at');
            }
        }

        // Apply sorting
        $queryBuilder->orderBy($query->sortBy, $query->sortDirection);

        // Paginate
        $paginator = $queryBuilder->paginate($query->perPage, ['*'], 'page', $query->page);

        return [
            'data' => $paginator->items(),
            'total' => $paginator->total(),
            'perPage' => $paginator->perPage(),
            'currentPage' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
        ];
    }

    private function mapToReadModels(array $data): array
    {
        $result = $data['result'];

        $result['data'] = $result['data']
            |> fn($items) => array_map(fn($item) => UserMapper::toDomain($item), $items)
            |> fn($users) => array_map(fn($user) => new UserListReadModel(
                id: $user->id,
                uuid: $user->uuid,
                name: $user->name,
                lastName: $user->lastName,
                email: $user->email,
                username: $user->username,
                profilePhotoPath: $user->profilePhotoPath,
                isEmailVerified: $user->isEmailVerified,
                createdAt: $user->createdAt,
            ), $users);

        return $result;
    }
}
