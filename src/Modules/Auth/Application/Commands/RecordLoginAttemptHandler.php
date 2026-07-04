<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Commands;

use Illuminate\Contracts\Events\Dispatcher;
use Modules\Auth\Domain\Events\LoginFailed;
use Modules\Auth\Domain\Events\UserLoggedIn;
use Modules\Auth\Domain\Ports\AccountLockoutPort;
use Modules\Auth\Domain\Ports\GeoLocatorPort;
use Modules\Auth\Domain\Ports\LoginAttemptRepositoryPort;
use Shared\Domain\Ports\AuditPort;

/**
 * Single source of truth for recording authentication attempts. Invoked from
 * BOTH the web auth-event listener and the stateless API controller so the
 * audit trail + lockout counter stay unified across transports.
 *
 * Lockout threshold: 10 failures inside the rolling window -> account locked
 * (the window/decay is owned by the AccountLockoutPort adapter).
 */
final readonly class RecordLoginAttemptHandler
{
    public function __construct(
        private LoginAttemptRepositoryPort $attempts,
        private AccountLockoutPort $lockout,
        private AuditPort $audit,
        private Dispatcher $events,
        private GeoLocatorPort $geo,
    ) {}

    public function recordSuccess(
        string $email,
        ?string $ipAddress,
        ?string $userAgent,
        string $userUuid,
        string $guard = 'web',
    ): void {
        $country = $this->geo->country($ipAddress);

        $this->lockout->clear($email);
        $this->attempts->record($email, $ipAddress, $userAgent, true, $userUuid, $guard, $country);

        $this->audit->log(
            event: 'auth.login',
            properties: [
                'email' => $email,
                'ip_address' => $ipAddress,
                'country' => $country,
                'guard' => $guard,
            ],
            logName: 'auth',
        );

        $this->events->dispatch(new UserLoggedIn($userUuid, $email, $ipAddress, $userAgent, $guard));
    }

    /** Records a failure and returns the resulting failure count. */
    #[\NoDiscard('The failure count drives the lockout decision')]
    public function recordFailure(
        string $email,
        ?string $ipAddress,
        ?string $userAgent,
        string $guard = 'web',
    ): int {
        $country = $this->geo->country($ipAddress);

        $count = $this->lockout->recordFailure($email);
        $this->attempts->record($email, $ipAddress, $userAgent, false, null, $guard, $country);

        $locked = $this->lockout->isLocked($email);

        $this->audit->log(
            event: 'auth.login_failed',
            properties: [
                'email' => $email,
                'ip_address' => $ipAddress,
                'country' => $country,
                'guard' => $guard,
                'failure_count' => $count,
                'locked' => $locked,
            ],
            logName: 'auth',
        );

        $this->events->dispatch(new LoginFailed($email, $ipAddress, $userAgent, $count, $locked));

        return $count;
    }
}
