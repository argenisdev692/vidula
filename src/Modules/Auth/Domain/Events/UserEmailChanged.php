<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Events;

/**
 * UserEmailChanged — Domain event fired when user email is changed.
 */
final readonly class UserEmailChanged
{
    public function __construct(
        public int $userId,
        public string $uuid,
        public ?string $oldEmail,
        public string $newEmail,
        public string $occurredAt,
    ) {
    }
}
