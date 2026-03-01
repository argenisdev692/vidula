<?php

declare(strict_types=1);

namespace Modules\Users\Domain\Policies;

use Modules\Users\Domain\Entities\User;

/**
 * CanManageUsersPolicy
 */
final readonly class CanManageUsersPolicy
{
    public function canCreate(User $actor): bool
    {
        // Simple logic for domain layer, fine-grained check in Application/Infrastructure
        return $actor->status->value === 'active';
    }

    public function canEdit(User $actor, User $target): bool
    {
        return $actor->status->value === 'active';
    }
}
