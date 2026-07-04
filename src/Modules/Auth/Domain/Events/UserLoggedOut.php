<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Events;

/**
 * Emitted after a user logs out (session invalidated or API token revoked).
 */
final readonly class UserLoggedOut
{
    public function __construct(
        public ?string $userUuid,
        public ?string $email,
        public string $guard,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable,
    ) {}
}
