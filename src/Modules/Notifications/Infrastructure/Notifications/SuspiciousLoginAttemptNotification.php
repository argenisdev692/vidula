<?php

declare(strict_types=1);

namespace Modules\Notifications\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * SuspiciousLoginAttemptNotification
 *
 * Queued notification sent via Horizon when a rate limit
 * is exceeded on auth routes. Alerts the account owner
 * that someone is attempting to access their account.
 *
 * Queue: 'notifications' (processed by Horizon)
 */
final class SuspiciousLoginAttemptNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Retry the job 3 times.
     */
    public int $tries = 3;

    /**
     * Back off exponentially: 10s, 30s, 60s.
     *
     * @var array<int, int>
     */
    public array $backoff = [10, 30, 60];

    public function __construct(
        private readonly string $ipAddress,
        private readonly string $userAgent,
        private readonly string $attemptedAt,
        private readonly string $route,
    ) {
        $this->onQueue('notifications');
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $userName = $notifiable->name ?? 'User';
        $browser = $this->parseBrowser($this->userAgent);

        return (new MailMessage())
            ->subject('⚠️ Suspicious Login Activity — Vidula')
            ->greeting("Hello {$userName},")
            ->line('We detected multiple failed login attempts on your Vidula account.')
            ->line('**Attempt Details:**')
            ->line("• **IP Address:** {$this->ipAddress}")
            ->line("• **Browser:** {$browser}")
            ->line("• **Time:** {$this->attemptedAt}")
            ->line("• **Target:** {$this->route}")
            ->line('If this was you, you can safely ignore this message. Your account has been temporarily locked for security — please wait a few minutes before trying again.')
            ->line('If this was **NOT** you, we strongly recommend:')
            ->line('1. Change your password immediately')
            ->line('2. Enable two-factor authentication (2FA)')
            ->line('3. Contact our support team')
            ->action('Secure My Account', url('/login'))
            ->salutation('— Vidula Security Team');
    }

    /**
     * Parse a user agent string into a readable browser name.
     */
    private function parseBrowser(string $ua): string
    {
        if (str_contains($ua, 'Chrome') && !str_contains($ua, 'Edg')) {
            return 'Google Chrome';
        }
        if (str_contains($ua, 'Edg')) {
            return 'Microsoft Edge';
        }
        if (str_contains($ua, 'Firefox')) {
            return 'Mozilla Firefox';
        }
        if (str_contains($ua, 'Safari') && !str_contains($ua, 'Chrome')) {
            return 'Apple Safari';
        }
        if (str_contains($ua, 'curl')) {
            return 'cURL (automated)';
        }
        if (str_contains($ua, 'Postman')) {
            return 'Postman (API tool)';
        }

        return 'Unknown Browser';
    }
}
