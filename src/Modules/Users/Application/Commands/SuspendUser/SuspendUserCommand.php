<?php

declare(strict_types=1);

namespace Modules\Users\Application\Commands\SuspendUser;

final readonly class SuspendUserCommand
{
    public function __construct(
        public string $uuid,
        public string $reason = ''
    ) {
    }
}
