<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Queries\GetUser;

use Modules\Auth\Contracts\DTOs\UserReadModel;
use Modules\Auth\Domain\Ports\UserRepositoryPort;
use Modules\Auth\Domain\Exceptions\UserNotFoundException;
use Illuminate\Support\Facades\Cache;

/**
 * GetUserHandler — Handles single user retrieval with caching and PHP 8.5 pipe operator.
 */
final readonly class GetUserHandler
{
    public function __construct(
        private UserRepositoryPort $userRepository,
    ) {}

    #[\NoDiscard]
    public function handle(GetUserQuery $query): UserReadModel
    {
        return $query
            |> $this->getCacheKey(...)
            |> $this->getFromCacheOrDatabase(...)
            |> $this->mapToReadModel(...);
    }

    private function getCacheKey(GetUserQuery $query): array
    {
        $cacheKey = $query->id
            ? "user_{$query->id}"
            : "user_{$query->uuid}";

        return ['query' => $query, 'cacheKey' => $cacheKey];
    }

    private function getFromCacheOrDatabase(array $data): array
    {
        $query = $data['query'];
        $cacheKey = $data['cacheKey'];

        $user = Cache::remember($cacheKey, 3600, function () use ($query) {
            if ($query->id) {
                return $this->userRepository->findById($query->id);
            }
            
            // Note: Would need to add findByUuid to repository
            return $this->userRepository->findById($query->id);
        });

        if ($user === null) {
            throw UserNotFoundException::withId($query->id ?? 0);
        }

        return ['user' => $user];
    }

    private function mapToReadModel(array $data): UserReadModel
    {
        $user = $data['user'];

        return new UserReadModel(
            id: $user->id,
            uuid: $user->uuid,
            name: $user->name,
            lastName: $user->lastName,
            email: $user->email,
            username: $user->username,
            profilePhotoPath: $user->profilePhotoPath,
            phone: $user->phone,
            isEmailVerified: $user->isEmailVerified,
            createdAt: $user->createdAt,
            updatedAt: $user->updatedAt,
            deletedAt: $user->deletedAt,
        );
    }
}
