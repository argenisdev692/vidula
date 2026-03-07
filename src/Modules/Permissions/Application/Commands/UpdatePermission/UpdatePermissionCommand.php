<?php

declare(strict_types=1);

namespace Modules\Permissions\Application\Commands\UpdatePermission;

use Modules\Permissions\Application\DTOs\UpdatePermissionDTO;

final readonly class UpdatePermissionCommand
{
    public function __construct(
        public string $uuid,
        public UpdatePermissionDTO $dto,
    ) {
    }
}
