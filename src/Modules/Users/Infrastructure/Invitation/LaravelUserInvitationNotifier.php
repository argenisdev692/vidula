<?php

declare(strict_types=1);

namespace Modules\Users\Infrastructure\Invitation;

use App\Models\User;
use Modules\Users\Domain\Ports\UserInvitationNotifierPort;
use Modules\Users\Infrastructure\Notifications\UserInvitationNotification;

/**
 * Adapter: maps {@see UserInvitationNotifierPort} onto the queued
 * {@see UserInvitationNotification} (Brevo mailer).
 */
final readonly class LaravelUserInvitationNotifier implements UserInvitationNotifierPort
{
    public function send(User $user, string $activationUrl, int $expiresInHours): void
    {
        $user->notify(new UserInvitationNotification($activationUrl, $expiresInHours));
    }
}
