<?php

declare(strict_types=1);

namespace Modules\Users\Infrastructure\Invitation;

use App\Models\User;
use Illuminate\Support\Facades\URL;
use Modules\Users\Domain\Ports\InvitationLinkPort;

/**
 * Builds the activation link as a temporary SIGNED route (Laravel 13).
 *
 * The signature is tamper-proof and bakes in the expiry, so no token row is
 * stored. Single-use is enforced downstream by the controller's state guard
 * (only a Pending user can activate).
 */
final readonly class SignedUrlInvitationAdapter implements InvitationLinkPort
{
    public function generate(User $user, int $expiresInHours): string
    {
        return URL::temporarySignedRoute(
            'users.activation.show',
            now()->addHours($expiresInHours),
            ['uuid' => $user->uuid],
        );
    }
}
