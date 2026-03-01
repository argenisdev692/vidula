<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Events;

/**
 * PasswordChanged — Fired when a user changes or resets their password.
 */
readonly class PasswordChanged
{
    public function __construct(
        public int $userId,
        public string $method,   // 'self_change' | 'otp_reset' | 'admin_reset'
        public string $occurredAt,
    ) {
    }
}
