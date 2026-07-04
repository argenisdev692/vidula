<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Listeners;

use App\Models\User;
use Modules\Auth\Domain\Events\LoginFailed;
use Modules\Auth\Infrastructure\Notifications\SecurityWarningNotification;

/**
 * Emails an English security warning when an account becomes locked after the
 * configured number of failed sign-in attempts (default 10 -> 15 min lock).
 *
 * Fires only on the threshold-crossing failure: once locked, further attempts
 * short-circuit before a new LoginFailed(locked: true) is dispatched.
 */
final readonly class LockoutWarningListener
{
    public function onLoginFailed(LoginFailed $event): void
    {
        if (! $event->locked) {
            return;
        }

        $user = User::query()->where('email', $event->email)->first();

        if ($user === null) {
            return;
        }

        $user->notify(new SecurityWarningNotification(
            attempts: $event->failureCount,
            lockMinutes: (int) config('security.lockout.decay_minutes', 15),
            ipAddress: $event->ipAddress,
            resetUrl: url('/forgot-password'),
        ));
    }
}
