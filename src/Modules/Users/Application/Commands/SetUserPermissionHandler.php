<?php

declare(strict_types=1);

namespace Modules\Users\Application\Commands;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Authorization\Domain\SystemRoles;
use Modules\Users\Domain\AssignableAccess;
use Shared\Domain\Ports\AuditPort;

/**
 * Grants or revokes a SINGLE direct permission on a user (instant toggle from the
 * dedicated per-user permissions screen). Authorization
 * (permission:ASSIGN_PERMISSIONS_USERS) is enforced at the route; the
 * anti-escalation invariant ({@see AssignableAccess}) is enforced here so an actor
 * can never grant a permission they do not hold. Written to the audit trail.
 */
final readonly class SetUserPermissionHandler
{
    public function __construct(private AuditPort $audit) {}

    public function handle(User $actor, User $target, string $permission, bool $granted): User
    {
        AssignableAccess::assertPermissionsAllowed(
            $actor->hasRole(SystemRoles::SUPER_ADMIN),
            array_values($actor->getAllPermissions()->pluck('name')->all()),
            [$permission],
        );

        DB::transaction(function () use ($target, $permission, $granted): void {
            if ($granted) {
                $target->givePermissionTo($permission);
            } else {
                $target->revokePermissionTo($permission);
            }
        });

        $this->audit->log(
            event: $granted ? 'user.permission.granted' : 'user.permission.revoked',
            subject: $target,
            properties: ['permission' => $permission],
            causer: $actor,
            logName: 'auth.access',
        );

        return $target->refresh()->load('permissions:id,name');
    }
}
