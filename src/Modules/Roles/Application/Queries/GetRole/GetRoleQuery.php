<?php

declare(strict_types=1);

namespace Modules\Roles\Application\Queries\GetRole;

final readonly class GetRoleQuery
{
    public function __construct(
        public string $uuid,
    ) {
    }
}
