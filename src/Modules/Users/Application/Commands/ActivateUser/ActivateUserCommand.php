<?php

declare(strict_types=1);

namespace Modules\Users\Application\Commands\ActivateUser;

final readonly class ActivateUserCommand
{
    public function __construct(
        public string $uuid
    ) {
    }
}
