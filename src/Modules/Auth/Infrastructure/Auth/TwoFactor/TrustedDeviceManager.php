<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Auth\TwoFactor;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Models\TrustedDeviceEloquentModel;

/**
 * "Trust this device for 30 days" (prompt §3). After a successful 2FA
 * challenge the user may mark the device as trusted; the raw token is stored
 * only in an encrypted, httpOnly cookie, while the server keeps its sha256
 * hash. A trusted, non-expired device skips the 2FA challenge on next login
 * (the password is still required).
 */
final readonly class TrustedDeviceManager
{
    private const string COOKIE_PREFIX = 'two_factor_trust_';

    private const int TTL_DAYS = 30;

    public function isTrusted(Request $request, User $user): bool
    {
        $token = $request->cookie($this->cookieName($user));

        if (! is_string($token) || $token === '') {
            return false;
        }

        $device = $user->trustedDevices()
            ->where('token_hash', hash('sha256', $token))
            ->where('expires_at', '>', now())
            ->first();

        if ($device === null) {
            return false;
        }

        $device->forceFill(['last_used_at' => now()])->save();

        return true;
    }

    public function trust(Request $request, User $user): TrustedDeviceEloquentModel
    {
        $token = Str::random(64);
        $expiresAt = now()->addDays(self::TTL_DAYS);

        $device = $user->trustedDevices()->create([
            'token_hash' => hash('sha256', $token),
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
            'last_used_at' => now(),
            'expires_at' => $expiresAt,
        ]);

        Cookie::queue(Cookie::make(
            name: $this->cookieName($user),
            value: $token,
            minutes: self::TTL_DAYS * 24 * 60,
            path: '/',
            domain: null,
            secure: $request->isSecure(),
            httpOnly: true,
            raw: false,
            sameSite: 'lax',
        ));

        return $device;
    }

    public function revoke(User $user, string $uuid): void
    {
        $user->trustedDevices()->where('uuid', $uuid)->delete();
    }

    private function cookieName(User $user): string
    {
        return self::COOKIE_PREFIX.$user->getKey();
    }
}
