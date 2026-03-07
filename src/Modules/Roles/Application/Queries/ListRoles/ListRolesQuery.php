<?php

declare(strict_types=1);

namespace Modules\Roles\Application\Queries\ListRoles;

use Modules\Roles\Application\DTOs\RoleFilterDTO;

final readonly class ListRolesQuery
{
    public function __construct(
        public RoleFilterDTO $filters,
    ) {
    }
}
