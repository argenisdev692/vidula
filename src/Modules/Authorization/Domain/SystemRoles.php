<?php

declare(strict_types=1);

namespace Modules\Authorization\Domain;

use Database\Seeders\RolePermissionSeeder;

/**
 * Foundation roles seeded by {@see RolePermissionSeeder}. They
 * are referenced across the codebase and tests by name, so the module treats them
 * as an invariant: a system role can never be renamed nor deleted through the CRUD
 * surface. This is the domain rule that lifts the module above a plain CRUD.
 */
final readonly class SystemRoles
{
    public const string SUPER_ADMIN = 'SUPER_ADMIN';

    /** @var list<string> */
    public const array PROTECTED = ['SUPER_ADMIN', 'ADMIN', 'MODERATOR', 'USER', 'GUEST'];

    public static function isProtected(string $roleName): bool
    {
        return in_array($roleName, self::PROTECTED, true);
    }
}
