<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Auth;

use App\Models\User;
use Carbon\CarbonImmutable;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Sanctum personal-access-token lifecycle for the API auth surface.
 *
 * Policy (prompt §6):
 *   - Rotate on every login: revoke prior tokens with the same device name
 *     before issuing a fresh one.
 *   - Expiration by privilege: admins/super-admins -> 1h, regular users -> 24h.
 */
final readonly class SanctumTokenService
{
    /** Privileged roles get the short-lived token. */
    private const array PRIVILEGED_ROLES = ['SUPER_ADMIN', 'ADMIN'];

    /** Issues a rotated token for the given device, revoking previous ones. */
    public function issueRotated(User $user, string $deviceName): NewAccessToken
    {
        $user->tokens()->where('name', $deviceName)->delete();

        return $user->createToken($deviceName, ['*'], $this->expiryFor($user));
    }

    /** Refreshes the caller's current token, preserving its device name. */
    public function refreshCurrent(User $user): NewAccessToken
    {
        $current = $user->currentAccessToken();
        $deviceName = $current instanceof PersonalAccessToken ? (string) $current->name : 'api';

        if ($current instanceof PersonalAccessToken) {
            $current->delete();
        }

        return $user->createToken($deviceName, ['*'], $this->expiryFor($user));
    }

    private function expiryFor(User $user): CarbonImmutable
    {
        return $user->hasAnyRole(self::PRIVILEGED_ROLES)
            ? CarbonImmutable::now()->addHour()
            : CarbonImmutable::now()->addDay();
    }
}
