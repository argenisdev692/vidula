<?php

declare(strict_types=1);

namespace Modules\Users\Domain\Ports;

use App\Models\User;

/**
 * Builds the one-time activation link a freshly-invited user receives by email.
 *
 * Default adapter signs a temporary route (tamper-proof + expiry baked into the
 * signature). Swappable for a DB-token strategy without touching callers (DIP).
 */
interface InvitationLinkPort
{
    public function generate(User $user, int $expiresInHours): string;
}
