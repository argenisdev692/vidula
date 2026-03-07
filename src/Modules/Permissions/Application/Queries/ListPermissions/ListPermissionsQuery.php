<?php

declare(strict_types=1);

namespace Modules\Permissions\Application\Queries\ListPermissions;

use Modules\Permissions\Application\DTOs\PermissionFilterDTO;

final readonly class ListPermissionsQuery
{
    public function __construct(
        public PermissionFilterDTO $filters,
    ) {
    }
}
