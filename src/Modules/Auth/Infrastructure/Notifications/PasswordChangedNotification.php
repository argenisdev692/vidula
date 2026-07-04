<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Security alert emailed to the user when their password changes (prompt §5).
 */
final class PasswordChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly ?string $ipAddress = null,
        private readonly ?string $changedAt = null,
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
            ->subject(__('Your password was changed'))
            ->view('emails.security.password-changed', [
                'ipAddress' => $this->ipAddress,
                'changedAt' => $this->changedAt,
            ]);
    }
}
