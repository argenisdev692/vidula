<?php

declare(strict_types=1);

namespace Modules\Users\Domain\Exceptions;

use DomainException;

/**
 * Raised when an actor tries to grant a role or direct permission they do not
 * themselves hold (OWASP A01 — broken access control / privilege escalation).
 * The Domain never imports Laravel HTTP; the controller translates this into a
 * flashed error / 403.
 */
final class PrivilegeEscalationException extends DomainException
{
    /**
     * @param  list<string>  $roleNames
     */
    public static function forRoles(array $roleNames): self
    {
        return new self(
            sprintf('You cannot assign roles you do not hold: %s.', implode(', ', $roleNames)),
        );
    }

    /**
     * @param  list<string>  $permissionNames
     */
    public static function forPermissions(array $permissionNames): self
    {
        return new self(
            sprintf('You cannot grant permissions you do not hold: %s.', implode(', ', $permissionNames)),
        );
    }
}
