<?php

declare(strict_types=1);

namespace Modules\Roles\Application\Commands\DeleteRole;

final readonly class DeleteRoleCommand
{
    public function __construct(
        public string $uuid,
    ) {
    }
}
