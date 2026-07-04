<?php

declare(strict_types=1);

namespace Modules\Users\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Raised when an invited user completes activation (password set + email
 * verified in a single step via the signed link).
 */
final readonly class UserActivated
{
    use Dispatchable;

    public function __construct(
        public string $uuid,
        public string $email,
    ) {}
}
