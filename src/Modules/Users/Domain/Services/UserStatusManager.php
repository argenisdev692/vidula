<?php

declare(strict_types=1);

namespace Modules\Users\Domain\Services;

use Modules\Users\Domain\Entities\User;
use Modules\Users\Domain\Ports\UserRepositoryPort;

/**
 * UserStatusManager — Domain Service
 */
final readonly class UserStatusManager
{
    public function __construct(
        private UserRepositoryPort $repository
    ) {
    }

    #[\NoDiscard]
    public function suspend(User $user): User
    {
        $suspendedUser = $user->suspend();
        $this->repository->save($suspendedUser);

        return $suspendedUser;
    }

    #[\NoDiscard]
    public function activate(User $user): User
    {
        $activatedUser = $user->activate();
        $this->repository->save($activatedUser);

        return $activatedUser;
    }
}
