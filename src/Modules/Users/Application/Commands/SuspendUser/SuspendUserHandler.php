<?php

declare(strict_types=1);

namespace Modules\Users\Application\Commands\SuspendUser;

use Modules\Users\Domain\Events\UserSuspended;
use Modules\Users\Domain\Exceptions\UserNotFoundException;
use Modules\Users\Domain\Ports\UserRepositoryPort;
use Modules\Users\Domain\Services\UserStatusManager;
use Shared\Domain\Events\DomainEventPublisher;

final readonly class SuspendUserHandler
{
    public function __construct(
        private UserRepositoryPort $userRepository,
        private UserStatusManager $statusManager
    ) {
    }

    public function handle(SuspendUserCommand $command): void
    {
        $user = $this->userRepository->findByUuid($command->uuid);

        if (null === $user) {
            throw UserNotFoundException::forUuid($command->uuid);
        }

        $this->statusManager->suspend($user);

        DomainEventPublisher::instance()->publish(
            new UserSuspended(
                aggregateId: $command->uuid,
                reason: $command->reason,
                occurredOn: now()->toDateTimeString()
            )
        );
    }
}
