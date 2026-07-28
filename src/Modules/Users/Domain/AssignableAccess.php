<?php

declare(strict_types=1);

namespace Modules\Users\Domain;

use Modules\Users\Domain\Exceptions\PrivilegeEscalationException;

/**
 * Anti-escalation invariant shared by every flow that attaches access to a user
 * (invite + access panel). Spatie does NOT stop an actor from granting a role or
 * permission they lack, so this is the single domain rule enforcing that an actor
 * may only delegate access they already hold. A SUPER_ADMIN is exempt (it holds
 * the full catalogue by definition).
 *
 * Accepts primitives only — Application maps Spatie/Eloquent actor state into
 * these arguments so Domain stays free of Laravel facades and Eloquent models.
 */
final readonly class AssignableAccess
{
    /**
     * @param  list<string>  $heldRoleNames
     * @param  list<string>  $roleNames
     *
     * @throws PrivilegeEscalationException
     */
    public static function assertRolesAllowed(bool $actorIsSuperAdmin, array $heldRoleNames, array $roleNames): void
    {
        if ($roleNames === [] || $actorIsSuperAdmin) {
            return;
        }

        $forbidden = array_values(array_diff($roleNames, $heldRoleNames));

        if ($forbidden !== []) {
            throw PrivilegeEscalationException::forRoles($forbidden);
        }
    }

    /**
     * @param  list<string>  $heldPermissionNames
     * @param  list<string>  $permissionNames
     *
     * @throws PrivilegeEscalationException
     */
    public static function assertPermissionsAllowed(bool $actorIsSuperAdmin, array $heldPermissionNames, array $permissionNames): void
    {
        if ($permissionNames === [] || $actorIsSuperAdmin) {
            return;
        }

        $forbidden = array_values(array_diff($permissionNames, $heldPermissionNames));

        if ($forbidden !== []) {
            throw PrivilegeEscalationException::forPermissions($forbidden);
        }
    }
}
