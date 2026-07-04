<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Security alert emailed when a sign-in is detected from a new device / IP
 * (prompt §6).
 */
final class NewDeviceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly ?string $ipAddress,
        private readonly ?string $userAgent,
        private readonly string $occurredAt,
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
            ->subject(__('New sign-in to your account'))
            ->view('emails.security.new-device', [
                'ipAddress' => $this->ipAddress,
                'userAgent' => $this->userAgent,
                'occurredAt' => $this->occurredAt,
            ]);
    }
}
