<?php

declare(strict_types=1);

namespace Modules\Users\Domain\Events;

/**
 * Raised when an admin invites a new user (Pending state created, link emailed).
 */
final readonly class UserInvited
{
    public function __construct(
        public string $uuid,
        public string $email,
        public ?string $invitedByUuid,
    ) {}
}
