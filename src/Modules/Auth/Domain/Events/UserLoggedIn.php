<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Events;

/**
 * Emitted after a successful authentication (web or API).
 */
final readonly class UserLoggedIn
{
    public function __construct(
        public string $userUuid,
        public string $email,
        public ?string $ipAddress,
        public ?string $userAgent,
        public string $guard,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable,
    ) {}
}
