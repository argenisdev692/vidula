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

        // Invalidate caches
        Cache::forget("user_read_{$command->uuid}");
        $this->invalidateListCache();
    }

    private function invalidateListCache(): void
    {
        try {
            Cache::tags(['users_list'])->flush();
        } catch (\Exception $e) {
            // Tags not supported, cache will expire naturally
        }
    }
}
