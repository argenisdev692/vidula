<?php

declare(strict_types=1);

namespace Modules\Users\Domain\Events;

/**
 * Raised when an invited user completes activation (password set + email
 * verified in a single step via the signed link).
 */
final readonly class UserActivated
{
    public function __construct(
        public string $uuid,
        public string $email,
    ) {}
}
