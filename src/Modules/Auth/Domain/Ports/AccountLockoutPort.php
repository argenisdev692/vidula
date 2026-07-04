<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Ports;

/**
 * Persistent brute-force lockout: an account is locked after a threshold of
 * failed attempts within a rolling window (prompt §2: 10 failures -> 15 min).
 * Shared by the web (Fortify) and API authentication paths so the counter is
 * unified across transports.
 */
interface AccountLockoutPort
{
    public function isLocked(string $email): bool;

    /** Records a failed attempt and returns the new failure count. */
    public function recordFailure(string $email): int;

    /** Clears the lockout counter (called on a successful authentication). */
    public function clear(string $email): void;

    /** Seconds remaining until the lock is released (0 when not locked). */
    public function availableIn(string $email): int;
}
