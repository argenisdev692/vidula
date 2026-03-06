<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\ExternalServices\Otp;

use Illuminate\Support\Arr;
use Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserEloquentModel as User;
use Modules\Notifications\Infrastructure\Notifications\SendOtpNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Domain\Events\OtpGenerated;
use Modules\Auth\Domain\Ports\OtpServicePort;
use Modules\Auth\Domain\ValueObjects\OtpCode;
use Modules\Auth\Domain\ValueObjects\UserEmail;

/**
 * CacheOtpAdapter — OTP implementation using Laravel Cache + Notifications.
 *
 * Generates a 6-digit code, stores a hashed version in cache (10 min TTL),
 * and dispatches a queued notification via Horizon.
 */
final class CacheOtpAdapter implements OtpServicePort
{
    private const TTL_SECONDS = 600; // 10 minutes
    private const RESEND_COOLDOWN_SECONDS = 60;
    private const MAX_ATTEMPTS = 5;

    public function send(string $identifier): void
    {
        $normalizedIdentifier = $this->normalizeIdentifier($identifier);

        if (Cache::has($this->cooldownKey($normalizedIdentifier))) {
            return;
        }

        $otp = OtpCode::generate();
        $expiresAt = time() + self::TTL_SECONDS;

        Cache::put(
            key: $this->cacheKey($normalizedIdentifier),
            value: [
                'hash' => Hash::make($otp->value),
                'attempts' => 0,
                'expires_at' => $expiresAt,
            ],
            ttl: self::TTL_SECONDS,
        );

        Cache::put(
            key: $this->cooldownKey($normalizedIdentifier),
            value: true,
            ttl: self::RESEND_COOLDOWN_SECONDS,
        );

        $user = User::where('email', $normalizedIdentifier)
            ->first();

        $user?->notify(new SendOtpNotification($otp->value));

        event(new OtpGenerated(
            identifier: $normalizedIdentifier,
            channel: 'mail',
            occurredAt: now()->toDateTimeString(),
        ));
    }

    public function verify(string $identifier, string $code): bool
    {
        $normalizedIdentifier = $this->normalizeIdentifier($identifier);
        $payload = Cache::get($this->cacheKey($normalizedIdentifier));

        if ($payload === null) {
            return false;
        }

        $hash = is_array($payload)
            ? Arr::get($payload, 'hash')
            : $payload;

        $attempts = is_array($payload)
            ? (int) Arr::get($payload, 'attempts', 0)
            : 0;

        $expiresAt = is_array($payload)
            ? (int) Arr::get($payload, 'expires_at', time() + self::TTL_SECONDS)
            : time() + self::TTL_SECONDS;

        if (!is_string($hash) || $attempts >= self::MAX_ATTEMPTS) {
            $this->invalidate($normalizedIdentifier);
            return false;
        }

        if (Hash::check($code, $hash)) {
            return true;
        }

        $attempts++;

        if ($attempts >= self::MAX_ATTEMPTS) {
            $this->invalidate($normalizedIdentifier);
            return false;
        }

        Cache::put(
            key: $this->cacheKey($normalizedIdentifier),
            value: [
                'hash' => $hash,
                'attempts' => $attempts,
                'expires_at' => $expiresAt,
            ],
            ttl: max(1, $expiresAt - time()),
        );

        return false;
    }

    public function invalidate(string $identifier): void
    {
        $normalizedIdentifier = $this->normalizeIdentifier($identifier);

        Cache::forget($this->cacheKey($normalizedIdentifier));
        Cache::forget($this->cooldownKey($normalizedIdentifier));
    }

    private function cacheKey(string $identifier): string
    {
        return 'otp:' . strtolower($identifier);
    }

    private function cooldownKey(string $identifier): string
    {
        return 'otp:cooldown:' . strtolower($identifier);
    }

    private function normalizeIdentifier(string $identifier): string
    {
        return (string) new UserEmail($identifier);
    }
}
