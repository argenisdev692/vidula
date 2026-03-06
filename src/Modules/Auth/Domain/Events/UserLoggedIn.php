<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Events;

/**
 * UserLoggedIn — Domain event fired after successful authentication.
 */
final readonly class UserLoggedIn
{
    public function __construct(
        public int $userId,
        public string $provider,
        public string $ipAddress,
        public string $userAgent,
        public string $occurredAt,
    ) {
    }
}
