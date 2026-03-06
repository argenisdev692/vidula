<?php

declare(strict_types=1);

namespace Modules\Users\Application\Commands\RestoreUser;

use Illuminate\Support\Facades\Cache;
use Modules\Users\Domain\Ports\UserRepositoryPort;
use Shared\Infrastructure\Audit\AuditInterface;

/**
 * RestoreUserHandler — Command handler for restoring a soft-deleted user.
 */
final readonly class RestoreUserHandler
{
    public function __construct(
        private UserRepositoryPort $repository,
        private AuditInterface $audit,
    ) {
    }

    public function handle(RestoreUserCommand $command): void
    {
        $this->repository->restore($command->uuid);

        Cache::forget("user_read_{$command->uuid}");

        try {
            Cache::tags(['users_list'])->flush();
        } catch (\Exception $e) {
        }

        $this->audit->log(
            logName: 'users.restored',
            description: "User restored: {$command->uuid}",
            properties: ['uuid' => $command->uuid],
        );
    }
}
