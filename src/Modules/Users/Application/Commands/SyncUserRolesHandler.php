<?php

declare(strict_types=1);

namespace Modules\Users\Application\Commands;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Users\Domain\AssignableAccess;
use Shared\Domain\Ports\AuditPort;

/**
 * Replaces a user's role assignments (sync semantics). Authorization
 * (permission:ASSIGN_ROLES_USERS) is enforced at the route; the anti-escalation
 * invariant ({@see AssignableAccess}) is enforced here as defence-in-depth so an
 * actor can never delegate a role they do not hold. Written to the audit trail.
 */
final readonly class SyncUserRolesHandler
{
    public function __construct(private AuditPort $audit) {}

    /**
     * @param  array<int, string>  $roles
     */
    public function handle(User $actor, User $target, array $roles): User
    {
        AssignableAccess::assertRolesAllowed($actor, $roles);

        DB::transaction(fn () => $target->syncRoles($roles));

        $this->audit->log(
            event: 'user.roles.synced',
            subject: $target,
            properties: ['roles' => $roles],
            causer: $actor,
            logName: 'auth.access',
        );

        return $target->refresh()->load('roles:id,name');
    }
}
