<?php

declare(strict_types=1);

namespace Modules\Permissions\Application\Commands\CreatePermission;

use Modules\Permissions\Application\DTOs\CreatePermissionDTO;

final readonly class CreatePermissionCommand
{
    public function __construct(
        public CreatePermissionDTO $dto,
    ) {
    }
}
