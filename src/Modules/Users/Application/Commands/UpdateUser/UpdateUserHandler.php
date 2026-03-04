<?php

declare(strict_types=1);

namespace Modules\Users\Application\Commands\UpdateUser;

use Modules\Users\Domain\Entities\User;
use Modules\Users\Domain\Exceptions\UserNotFoundException;
use Modules\Users\Domain\Ports\UserRepositoryPort;
use Shared\Infrastructure\Audit\AuditInterface;
use Illuminate\Support\Facades\Cache;

/**
 * UpdateUserHandler — Validates user existence, then delegates update to the repository.
 */
final readonly class UpdateUserHandler
{
    public function __construct(
        private UserRepositoryPort $repository,
        private AuditInterface $audit,
    ) {
    }

    public function handle(UpdateUserCommand $command): User
    {
        $existing = $this->repository->findByUuid($command->uuid);

        if ($existing === null) {
            throw UserNotFoundException::forUuid($command->uuid);
        }

        $user = $this->repository->update($command->uuid, $command->dto->toArray());

        // Invalidate caches
        Cache::forget("user_read_{$command->uuid}");
        $this->invalidateListCache();

        // Audit business action
        $this->audit->log(
            logName: 'users.updated',
            description: "User updated: {$command->uuid}",
            properties: ['uuid' => $command->uuid, 'changes' => $command->dto->toArray()],
        );

        return $user;
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
