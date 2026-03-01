<?php

declare(strict_types=1);

namespace Modules\Users\Domain\Services;

use Modules\Users\Domain\Entities\UserProfile;
use Modules\Users\Domain\Ports\UserProfileRepositoryPort;

/**
 * UserProfileManager — Domain Service
 */
final readonly class UserProfileManager
{
    public function __construct(
        private UserProfileRepositoryPort $repository
    ) {
    }

    #[\NoDiscard]
    public function updateProfile(UserProfile $profile): void
    {
        $this->repository->save($profile);
    }
}
