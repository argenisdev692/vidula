<?php

declare(strict_types=1);

namespace Modules\Authorization\Application\Commands;

use Modules\Authorization\Domain\Ports\PermissionRepositoryPort;

/**
 * Restores a suspended permission by UUID. Authorization
 * (permission:RESTORE_PERMISSIONS) is enforced at the route.
 */
final readonly class RestorePermissionHandler
{
    public function __construct(private PermissionRepositoryPort $permissions) {}

    public function handle(string $uuid): bool
    {
        return $this->permissions->restore($uuid);
    }
}
