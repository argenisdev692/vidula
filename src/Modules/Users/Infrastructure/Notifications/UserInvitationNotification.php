<?php

declare(strict_types=1);

namespace Modules\Users\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Queued invitation email carrying the signed activation link (all auth mail
 * goes through the queue). The notification is "dumb" — the link is built by
 * InvitationLinkPort and passed in.
 */
final class UserInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $activationUrl,
        private readonly int $expiresInHours,
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
            ->subject(__('You have been invited to :app', ['app' => config('app.name')]))
            ->greeting(__('Welcome!'))
            ->line(__('An administrator has created an account for you.'))
            ->line(__('Click the button below to set your password and activate your account.'))
            ->action(__('Activate account'), $this->activationUrl)
            ->line(__('This invitation link expires in :hours hours.', ['hours' => $this->expiresInHours]))
            ->line(__('If you were not expecting this invitation, you can ignore this email.'));
    }
}
