<?php

declare(strict_types=1);

namespace Modules\Permissions\Application\Queries\GetPermission;

final readonly class GetPermissionQuery
{
    public function __construct(
        public string $uuid,
    ) {
    }
}
