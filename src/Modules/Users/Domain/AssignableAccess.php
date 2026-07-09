<?php

declare(strict_types=1);

namespace Modules\Users\Domain;

use App\Models\User;
use Modules\Authorization\Domain\SystemRoles;
use Modules\Users\Domain\Exceptions\PrivilegeEscalationException;

/**
 * Anti-escalation invariant shared by every flow that attaches access to a user
 * (invite + access panel). Spatie does NOT stop an actor from granting a role or
 * permission they lack, so this is the single domain rule enforcing that an actor
 * may only delegate access they already hold. A SUPER_ADMIN is exempt (it holds
 * the full catalogue by definition).
 */
final readonly class AssignableAccess
{
    /**
     * @param  list<string>  $roleNames
     *
     * @throws PrivilegeEscalationException
     */
    public static function assertRolesAllowed(User $actor, array $roleNames): void
    {
        if ($roleNames === [] || $actor->hasRole(SystemRoles::SUPER_ADMIN)) {
            return;
        }

        $forbidden = array_values(array_diff($roleNames, $actor->getRoleNames()->all()));

        if ($forbidden !== []) {
            throw PrivilegeEscalationException::forRoles($forbidden);
        }
    }

    /**
     * @param  list<string>  $permissionNames
     *
     * @throws PrivilegeEscalationException
     */
    public static function assertPermissionsAllowed(User $actor, array $permissionNames): void
    {
        if ($permissionNames === [] || $actor->hasRole(SystemRoles::SUPER_ADMIN)) {
            return;
        }

        $held = $actor->getAllPermissions()->pluck('name')->all();
        $forbidden = array_values(array_diff($permissionNames, $held));

        if ($forbidden !== []) {
            throw PrivilegeEscalationException::forPermissions($forbidden);
        }
    }
}
