<?php

declare(strict_types=1);

namespace Modules\Users\Application\Commands;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Users\Application\DTOs\UserAccessData;
use Modules\Users\Domain\AssignableAccess;
use Shared\Domain\Ports\AuditPort;

/**
 * Replaces a user's roles and direct permission grants in one unit of work.
 * Authorization (permission:ASSIGN_ROLES_USERS / ASSIGN_PERMISSIONS_USERS) is
 * enforced at the route; the anti-escalation invariant ({@see AssignableAccess})
 * is enforced here as defence-in-depth so an actor can never delegate access they
 * do not themselves hold. The change is written to the audit trail.
 */
final readonly class SyncUserAccessHandler
{
    public function __construct(private AuditPort $audit) {}

    public function handle(User $actor, User $target, UserAccessData $data): User
    {
        AssignableAccess::assertRolesAllowed($actor, $data->roles);
        AssignableAccess::assertPermissionsAllowed($actor, $data->directPermissions);

        DB::transaction(function () use ($target, $data): void {
            $target->syncRoles($data->roles);
            $target->syncPermissions($data->directPermissions);
        });

        $this->audit->log(
            event: 'user.access.synced',
            subject: $target,
            properties: [
                'roles' => $data->roles,
                'direct_permissions' => $data->directPermissions,
            ],
            causer: $actor,
            logName: 'auth.access',
        );

        return $target->refresh()->load(['roles:id,name', 'permissions:id,name']);
    }
}
