<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\ExternalServices\Otp;

use Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserEloquentModel as User;
use Modules\Notifications\Infrastructure\Notifications\SendOtpNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Domain\Ports\OtpServicePort;
use Modules\Auth\Domain\ValueObjects\OtpCode;

/**
 * CacheOtpAdapter — OTP implementation using Laravel Cache + Notifications.
 *
 * Generates a 6-digit code, stores a hashed version in cache (10 min TTL),
 * and dispatches a queued notification via Horizon.
 */
final class CacheOtpAdapter implements OtpServicePort
{
    private const TTL_SECONDS = 600; // 10 minutes

    public function send(string $identifier): void
    {
        $otp = OtpCode::generate();

        Cache::put(
            key: $this->cacheKey($identifier),
            value: Hash::make($otp->value),
            ttl: self::TTL_SECONDS,
        );

        $user = User::where('email', $identifier)
            ->orWhere('phone', $identifier)
            ->first();

        $user?->notify(new SendOtpNotification($otp->value));
    }

    public function verify(string $identifier, string $code): bool
    {
        $hashed = Cache::get($this->cacheKey($identifier));

        if ($hashed === null) {
            return false;
        }

        return Hash::check($code, $hashed);
    }

    public function invalidate(string $identifier): void
    {
        Cache::forget($this->cacheKey($identifier));
    }

    private function cacheKey(string $identifier): string
    {
        return 'otp:' . strtolower($identifier);
    }
}
