<?php

declare(strict_types=1);

namespace Modules\Users\Application\Commands\RestoreUser;

use Modules\Users\Domain\Ports\UserRepositoryPort;
use Illuminate\Support\Facades\Cache;

/**
 * RestoreUserHandler — Command handler for restoring a soft-deleted user.
 */
final readonly class RestoreUserHandler
{
    public function __construct(
        private UserRepositoryPort $repository,
    ) {
    }

    public function handle(RestoreUserCommand $command): void
    {
        $this->repository->restore($command->uuid);
        
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
