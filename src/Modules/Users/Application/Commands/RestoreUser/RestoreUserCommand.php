<?php

declare(strict_types=1);

namespace Modules\Users\Application\Commands\RestoreUser;

final readonly class RestoreUserCommand
{
    public function __construct(
        public string $uuid,
    ) {
    }
}
