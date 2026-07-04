<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Lockout;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Str;
use Modules\Auth\Domain\Ports\AccountLockoutPort;

/**
 * Redis-backed account lockout (project cache store is Redis — BACKEND-PHP §0).
 *
 * Threshold + window per prompt §2: 10 failed attempts -> locked for 15 minutes.
 * The counter is keyed by email so the lock is unified across the web (Fortify)
 * and API authentication paths.
 */
final readonly class CacheAccountLockoutAdapter implements AccountLockoutPort
{
    public function __construct(private Cache $cache) {}

    public function isLocked(string $email): bool
    {
        return $this->currentCount($email) >= $this->threshold();
    }

    public function recordFailure(string $email): int
    {
        $count = $this->currentCount($email) + 1;
        $expiresAt = now()->addSeconds($this->decaySeconds());

        $this->cache->put($this->countKey($email), $count, $expiresAt);
        $this->cache->put($this->untilKey($email), $expiresAt->getTimestamp(), $expiresAt);

        return $count;
    }

    public function clear(string $email): void
    {
        $this->cache->forget($this->countKey($email));
        $this->cache->forget($this->untilKey($email));
    }

    public function availableIn(string $email): int
    {
        if (! $this->isLocked($email)) {
            return 0;
        }

        $until = (int) $this->cache->get($this->untilKey($email), 0);

        return max(0, $until - now()->getTimestamp());
    }

    private function currentCount(string $email): int
    {
        return (int) $this->cache->get($this->countKey($email), 0);
    }

    private function threshold(): int
    {
        return (int) config('security.lockout.max_attempts', 10);
    }

    private function decaySeconds(): int
    {
        return (int) config('security.lockout.decay_minutes', 15) * 60;
    }

    private function countKey(string $email): string
    {
        return 'auth:lockout:'.Str::lower($email);
    }

    private function untilKey(string $email): string
    {
        return 'auth:lockout:'.Str::lower($email).':until';
    }
}
