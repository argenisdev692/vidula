<?php

declare(strict_types=1);

namespace Modules\Roles\Application\Commands\CreateRole;

use Modules\Roles\Application\DTOs\CreateRoleDTO;

final readonly class CreateRoleCommand
{
    public function __construct(
        public CreateRoleDTO $dto,
    ) {
    }
}
