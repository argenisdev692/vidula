<?php

declare(strict_types=1);

namespace Modules\Permissions\Application\Commands\DeletePermission;

final readonly class DeletePermissionCommand
{
    public function __construct(
        public string $uuid,
    ) {
    }
}
