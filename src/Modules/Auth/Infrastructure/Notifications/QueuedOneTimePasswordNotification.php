<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Notifications;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Spatie\OneTimePasswords\Notifications\OneTimePasswordNotification;

/**
 * Queued one-time-password notification (all auth mail goes through the queue).
 *
 * Used for every emailed 6-digit code: passwordless login, account activation
 * (email verification) and password reset. Validity is per-purpose — login uses
 * the short default (security.otp.login_minutes, ~2 min) and email flows use the
 * longer window (security.otp.email_minutes, ~30 min) passed via
 * sendOneTimePassword($minutes). A queue worker MUST be running (Redis queue) so
 * the code arrives well within its validity window.
 *
 * Overrides the package's plain markdown mail with the branded Blade layout
 * (emails/layout.blade.php) so the code email matches the other security mails.
 */
final class QueuedOneTimePasswordNotification extends OneTimePasswordNotification implements ShouldQueue
{
    public function toMail(object $notifiable): MailMessage
    {
        $expiresAt = $this->oneTimePassword->expires_at;
        $minutes = $expiresAt instanceof Carbon
            ? max(1, (int) ceil(Carbon::now()->diffInMinutes($expiresAt, false)))
            : null;

        return (new MailMessage)
            ->subject(__('Your verification code: :code', ['code' => $this->oneTimePassword->password]))
            ->view('emails.security.one-time-password', [
                'code' => $this->oneTimePassword->password,
                'minutes' => $minutes,
            ]);
    }
}
