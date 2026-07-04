<?php

declare(strict_types=1);

namespace Modules\Users\Application\Commands;

use App\Models\User;
use Modules\Users\Domain\Ports\InvitationLinkPort;
use Modules\Users\Infrastructure\Notifications\UserInvitationNotification;

/**
 * Re-emails a fresh signed activation link to a still-Pending user (e.g. the
 * first link expired). No-op for already-activated users (guarded by caller).
 */
final readonly class ResendInvitationHandler
{
    private const LINK_TTL_HOURS = 72;

    public function __construct(private InvitationLinkPort $links) {}

    public function handle(User $user): void
    {
        $link = $this->links->generate($user, self::LINK_TTL_HOURS);

        $user->forceFill(['invited_at' => now()])->save();
        $user->notify(new UserInvitationNotification($link, self::LINK_TTL_HOURS));
    }
}
