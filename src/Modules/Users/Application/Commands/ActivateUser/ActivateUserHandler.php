<?php

declare(strict_types=1);

namespace Modules\Users\Application\Commands\ActivateUser;

use Illuminate\Support\Facades\Cache;
use Modules\Users\Domain\Events\UserActivated;
use Modules\Users\Domain\Exceptions\UserNotFoundException;
use Modules\Users\Domain\Ports\UserRepositoryPort;
use Modules\Users\Domain\Services\UserStatusManager;
use Shared\Domain\Events\DomainEventPublisher;
use Shared\Infrastructure\Audit\AuditInterface;

final readonly class ActivateUserHandler
{
    public function __construct(
        private UserRepositoryPort $userRepository,
        private UserStatusManager $statusManager,
        private AuditInterface $audit,
    ) {
    }

    public function handle(ActivateUserCommand $command): void
    {
        $user = $this->userRepository->findByUuid($command->uuid);

        if (null === $user) {
            throw UserNotFoundException::forUuid($command->uuid);
        }

        $this->statusManager->activate($user);

        Cache::forget("user_read_{$command->uuid}");

        try {
            Cache::tags(['users_list'])->flush();
        } catch (\Exception $e) {
        }

        DomainEventPublisher::instance()->publish(
            new UserActivated(
                aggregateId: $command->uuid,
                occurredOn: now()->toDateTimeString()
            )
        );

        $this->audit->log(
            logName: 'users.activated',
            description: "User activated: {$command->uuid}",
            properties: ['uuid' => $command->uuid],
        );
    }
}
