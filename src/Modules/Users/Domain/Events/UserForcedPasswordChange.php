<?php

declare(strict_types=1);

namespace Modules\Users\Domain\Events;

/**
 * Raised when an admin forces a user to change their password on next login.
 */
final readonly class UserForcedPasswordChange
{
    public function __construct(
        public string $uuid,
    ) {}
}
