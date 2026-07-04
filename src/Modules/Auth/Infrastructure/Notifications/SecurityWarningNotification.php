<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * English warning emailed when an account is locked after too many failed
 * sign-in attempts (default 10 strikes -> 15 min lock, see config/security.php).
 * Uses the branded Blade layout.
 */
final class SecurityWarningNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $attempts,
        private readonly int $lockMinutes,
        private readonly ?string $ipAddress,
        private readonly ?string $resetUrl = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Security alert: your account has been temporarily locked'))
            ->view('emails.security.warning', [
                'attempts' => $this->attempts,
                'lockMinutes' => $this->lockMinutes,
                'ipAddress' => $this->ipAddress,
                'occurredAt' => now()->toDayDateTimeString(),
                'resetUrl' => $this->resetUrl,
            ]);
    }
}
