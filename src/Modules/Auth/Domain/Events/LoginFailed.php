<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Events;

/**
 * Emitted on every failed authentication attempt. Carries the failure count so
 * downstream listeners can react when the account crosses the lockout
 * threshold.
 */
final readonly class LoginFailed
{
    public function __construct(
        public string $email,
        public ?string $ipAddress,
        public ?string $userAgent,
        public int $failureCount,
        public bool $locked,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable,
    ) {}
}
