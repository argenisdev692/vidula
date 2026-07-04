<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;
use Modules\Auth\Application\Commands\RecordLoginAttemptHandler;
use Modules\Auth\Domain\Events\UserLoggedOut;
use Shared\Domain\Ports\AuditPort;

use function event;

/**
 * Bridges Laravel's session-guard auth events (fired by Fortify on the web
 * path) into the unified audit trail + lockout counter. Lives in Infrastructure
 * because it reads the current HTTP Request (IP / user agent).
 *
 * Wired explicitly in AuthServiceProvider::boot() (reliable for module paths,
 * unlike attribute auto-discovery which only scans app/Listeners).
 */
final readonly class WebAuthenticationListener
{
    public function __construct(
        private RecordLoginAttemptHandler $attempts,
        private AuditPort $audit,
        private Request $request,
    ) {}

    public function onLogin(Login $event): void
    {
        $user = $event->user;

        $this->attempts->recordSuccess(
            email: (string) ($user->getAttribute('email') ?? ''),
            ipAddress: $this->request->ip(),
            userAgent: $this->request->userAgent(),
            userUuid: (string) ($user->getAttribute('uuid') ?? ''),
            guard: 'web',
        );
    }

    public function onFailed(Failed $event): void
    {
        $email = (string) ($event->credentials['email'] ?? '');

        if ($email === '') {
            return;
        }

        (void) $this->attempts->recordFailure(
            email: $email,
            ipAddress: $this->request->ip(),
            userAgent: $this->request->userAgent(),
            guard: 'web',
        );
    }

    public function onLockout(Lockout $event): void
    {
        $this->audit->log(
            event: 'auth.rate_limited',
            properties: [
                'email' => (string) $event->request->input('email'),
                'ip_address' => $event->request->ip(),
            ],
            logName: 'auth',
        );
    }

    public function onLogout(Logout $event): void
    {
        $user = $event->user;

        $this->audit->log(
            event: 'auth.logout',
            properties: ['guard' => (string) $event->guard],
            logName: 'auth',
        );

        event(new UserLoggedOut(
            userUuid: $user?->getAttribute('uuid'),
            email: $user?->getAttribute('email'),
            guard: (string) $event->guard,
        ));
    }
}
