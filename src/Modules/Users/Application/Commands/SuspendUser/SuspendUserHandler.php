<?php

declare(strict_types=1);

namespace Modules\Users\Application\Commands\SuspendUser;

use Illuminate\Support\Facades\Cache;
use Modules\Users\Domain\Events\UserSuspended;
use Modules\Users\Domain\Exceptions\UserNotFoundException;
use Modules\Users\Domain\Ports\UserRepositoryPort;
use Modules\Users\Domain\Services\UserStatusManager;
use Shared\Domain\Events\DomainEventPublisher;
use Shared\Infrastructure\Audit\AuditInterface;

final readonly class SuspendUserHandler
{
    public function __construct(
        private UserRepositoryPort $userRepository,
        private UserStatusManager $statusManager,
        private AuditInterface $audit,
    ) {
    }

    public function handle(SuspendUserCommand $command): void
    {
        $user = $this->userRepository->findByUuid($command->uuid);

        if (null === $user) {
            throw UserNotFoundException::forUuid($command->uuid);
        }

        $this->statusManager->suspend($user);

        Cache::forget("user_read_{$command->uuid}");

        try {
            Cache::tags(['users_list'])->flush();
        } catch (\Exception $e) {
        }

        DomainEventPublisher::instance()->publish(
            new UserSuspended(
                aggregateId: $command->uuid,
                reason: $command->reason,
                occurredOn: now()->toDateTimeString()
            )
        );

        $this->audit->log(
            logName: 'users.suspended',
            description: "User suspended: {$command->uuid}",
            properties: ['uuid' => $command->uuid, 'reason' => $command->reason],
        );
    }
}
