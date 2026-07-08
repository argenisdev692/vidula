<?php

declare(strict_types=1);

namespace Modules\Authorization\Application\Commands;

use Modules\Authorization\Application\DTOs\PermissionData;
use Modules\Authorization\Domain\Ports\PermissionRepositoryPort;
use Modules\Authorization\Infrastructure\Persistence\Eloquent\Models\Permission;

/**
 * Creates a permission on the `web` guard. Authorization
 * (permission:CREATE_PERMISSIONS) is enforced at the route.
 */
final readonly class CreatePermissionHandler
{
    public function __construct(private PermissionRepositoryPort $permissions) {}

    #[\NoDiscard]
    public function handle(PermissionData $data): Permission
    {
        $name = $data->name |> trim(...);

        return $this->permissions->create(['name' => $name, 'guard_name' => 'web']);
    }
}
