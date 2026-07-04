<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Modules\Auth\Infrastructure\Notifications\NewDeviceNotification;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Models\LoginAttemptEloquentModel;
use Shared\Domain\Ports\AuditPort;

/**
 * Emails a security alert when a user signs in from a device/IP not seen
 * before (prompt §6). Registered AFTER WebAuthenticationListener so the current
 * successful attempt is already recorded; a prior count > 1 means the
 * device+IP combination is known.
 */
final readonly class NewDeviceListener
{
    public function __construct(
        private AuditPort $audit,
        private Request $request,
    ) {}

    public function onLogin(Login $event): void
    {
        $user = $event->user;
        $uuid = (string) ($user->getAttribute('uuid') ?? '');

        if ($uuid === '') {
            return;
        }

        $ip = $this->request->ip();
        $userAgent = $this->request->userAgent();

        $seenCount = LoginAttemptEloquentModel::query()
            ->where('user_uuid', $uuid)
            ->where('successful', true)
            ->where('ip_address', $ip)
            ->where('user_agent', $userAgent)
            ->count();

        // The current sign-in is already recorded; > 1 means it is a known device.
        if ($seenCount > 1) {
            return;
        }

        $user->notify(new NewDeviceNotification($ip, $userAgent, now()->toDayDateTimeString()));

        $this->audit->log(
            event: 'auth.new_device',
            subject: $user,
            properties: ['ip_address' => $ip],
            causer: $user,
            logName: 'auth',
        );
    }
}
