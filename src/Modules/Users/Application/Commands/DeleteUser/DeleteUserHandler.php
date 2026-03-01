<?php

declare(strict_types=1);

namespace Modules\Users\Application\Commands\DeleteUser;

use Modules\Users\Domain\Exceptions\UserNotFoundException;
use Modules\Users\Domain\Ports\UserRepositoryPort;
use Illuminate\Support\Facades\Cache;

/**
 * DeleteUserHandler — Validates user existence, then performs soft-delete via repository.
 */
final readonly class DeleteUserHandler
{
    public function __construct(
        private UserRepositoryPort $repository,
    ) {
    }

    public function handle(DeleteUserCommand $command): void
    {
        $existing = $this->repository->findByUuid($command->uuid);

        if ($existing === null) {
            throw UserNotFoundException::withUuid($command->uuid);
        }

        $this->repository->softDelete($command->uuid);

        // Clear individual user cache
        Cache::forget("user_{$command->uuid}");
        
        // Clear users list cache by pattern (requires Redis/Memcached)
        // For simplicity, we rely on TTL (15 min) or use tags in production
        try {
            Cache::tags(['users_list'])->flush();
        } catch (\Exception $e) {
            // Tags not supported, cache will expire naturally
        }
    }
}
