<?php

declare(strict_types=1);

namespace Modules\Roles\Application\Commands\UpdateRole;

use Modules\Roles\Application\DTOs\UpdateRoleDTO;

final readonly class UpdateRoleCommand
{
    public function __construct(
        public string $uuid,
        public UpdateRoleDTO $dto,
    ) {
    }
}
