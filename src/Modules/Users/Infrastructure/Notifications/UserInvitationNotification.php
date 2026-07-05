<?php

declare(strict_types=1);

namespace Modules\Users\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Shared\Infrastructure\Company\CompanyProfile;

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
        $company = CompanyProfile::data()['name'];

        return (new MailMessage)
            ->subject(__('You have been invited to :app', ['app' => $company]))
            ->view('emails.invitation', [
                'company' => $company,
                'activationUrl' => $this->activationUrl,
                'expiresInHours' => $this->expiresInHours,
            ]);
    }
}
