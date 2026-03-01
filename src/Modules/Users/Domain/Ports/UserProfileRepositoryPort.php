<?php

declare(strict_types=1);

namespace Modules\Users\Domain\Ports;

use Modules\Users\Domain\Entities\UserProfile;
use Modules\Users\Domain\ValueObjects\UserId;

/**
 * UserProfileRepositoryPort
 */
interface UserProfileRepositoryPort
{
    public function findByUserId(UserId $userId): ?UserProfile;

    public function save(UserProfile $profile): void;

    public function delete(UserId $userId): void;
}
