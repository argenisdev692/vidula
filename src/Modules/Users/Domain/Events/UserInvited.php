<?php

declare(strict_types=1);

namespace Modules\Users\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Raised when an admin invites a new user (Pending state created, link emailed).
 */
final readonly class UserInvited
{
    use Dispatchable;

    public function __construct(
        public string $uuid,
        public string $email,
        public ?string $invitedByUuid,
    ) {}
}
