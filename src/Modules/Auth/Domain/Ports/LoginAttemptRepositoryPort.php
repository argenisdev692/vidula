<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Ports;

/**
 * Persists the audit trail of every authentication attempt (success or
 * failure) with IP + country + user agent (prompt §2 / §7). Failed attempts
 * may carry an unknown email, so there is no required FK to users.
 */
interface LoginAttemptRepositoryPort
{
    public function record(
        string $email,
        ?string $ipAddress,
        ?string $userAgent,
        bool $successful,
        ?string $userUuid = null,
        string $guard = 'web',
        ?string $country = null,
    ): void;
}
