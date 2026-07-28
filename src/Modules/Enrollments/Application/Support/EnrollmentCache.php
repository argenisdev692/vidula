<?php

declare(strict_types=1);

namespace Modules\Enrollments\Application\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Redis-tagged list cache for enrollments (CACHE_STORE=redis).
 * Detail/Get is intentionally uncached — admin mutations need a live Eloquent
 * instance (same rationale as Portfolio: cache earns complexity on hot reads).
 * Without tag support (array driver), caching is skipped.
 */
final readonly class EnrollmentCache
{
    public const string LIST_TAG = 'enrollments_list';

    public static function listKey(string $fingerprint): string
    {
        return 'enrollments:list:'.$fingerprint;
    }

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public static function rememberList(string $fingerprint, callable $callback): mixed
    {
        $key = self::listKey($fingerprint);
        $ttl = now()->addMinutes(5);

        try {
            return Cache::tags([self::LIST_TAG])->remember($key, $ttl, $callback);
        } catch (\BadMethodCallException) {
            return $callback();
        }
    }

    public static function flush(): void
    {
        try {
            Cache::tags([self::LIST_TAG])->flush();
        } catch (\BadMethodCallException) {
            // No tagged cache in this environment.
        }
    }
}
