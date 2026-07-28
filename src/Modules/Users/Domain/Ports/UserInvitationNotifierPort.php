<?php

declare(strict_types=1);

namespace Modules\Users\Domain\Ports;

use App\Models\User;

/**
 * Delivers the invitation email carrying the signed activation link.
 * Application depends on this port; Infrastructure owns the Laravel Notification.
 */
interface UserInvitationNotifierPort
{
    public function send(User $user, string $activationUrl, int $expiresInHours): void;
}
