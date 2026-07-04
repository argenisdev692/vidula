<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Listeners;

use Laravel\Fortify\Events\RecoveryCodeReplaced;
use Laravel\Fortify\Events\RecoveryCodesGenerated;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationEnabled;
use Shared\Domain\Ports\AuditPort;

/**
 * Audits every 2FA lifecycle action (prompt §7): enable / confirm / disable,
 * recovery-code regeneration, and backup-code usage. Wired in
 * AuthServiceProvider. Never logs secrets or the codes themselves.
 */
final readonly class TwoFactorAuditListener
{
    public function __construct(private AuditPort $audit) {}

    public function onEnabled(TwoFactorAuthenticationEnabled $event): void
    {
        $this->record('auth.2fa_enabled', $event->user);
    }

    public function onConfirmed(TwoFactorAuthenticationConfirmed $event): void
    {
        $this->record('auth.2fa_confirmed', $event->user);
    }

    public function onDisabled(TwoFactorAuthenticationDisabled $event): void
    {
        $this->record('auth.2fa_disabled', $event->user);
    }

    public function onRecoveryCodesGenerated(RecoveryCodesGenerated $event): void
    {
        $this->record('auth.2fa_recovery_codes_generated', $event->user);
    }

    public function onRecoveryCodeReplaced(RecoveryCodeReplaced $event): void
    {
        $this->record('auth.2fa_backup_code_used', $event->user);
    }

    private function record(string $event, object $user): void
    {
        $this->audit->log(
            event: $event,
            subject: $user,
            causer: $user,
            logName: 'auth',
        );
    }
}
