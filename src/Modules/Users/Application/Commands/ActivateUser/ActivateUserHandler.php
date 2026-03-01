<?php

declare(strict_types=1);

namespace Modules\Users\Application\Commands\ActivateUser;

use Modules\Users\Domain\Events\UserActivated;
use Modules\Users\Domain\Exceptions\UserNotFoundException;
use Modules\Users\Domain\Ports\UserRepositoryPort;
use Modules\Users\Domain\Services\UserStatusManager;
use Shared\Domain\Events\DomainEventPublisher;

final readonly class ActivateUserHandler
{
    public function __construct(
        private UserRepositoryPort $userRepository,
        private UserStatusManager $statusManager
    ) {
    }

    public function handle(ActivateUserCommand $command): void
    {
        $user = $this->userRepository->findByUuid($command->uuid);

        if (null === $user) {
            throw UserNotFoundException::forUuid($command->uuid);
        }

        $this->statusManager->activate($user);

        DomainEventPublisher::instance()->publish(
            new UserActivated(
                aggregateId: $command->uuid,
                occurredOn: now()->toDateTimeString()
            )
        );
    }
}
