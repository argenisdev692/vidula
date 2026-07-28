<?php

declare(strict_types=1);

namespace Modules\Portfolio\Application\Support;

use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Tagged cache for the anonymous public portfolio feed.
 * Called by every Command handler that mutates a portfolio or its gallery, so
 * the landing-page feed never serves stale data past a save.
 *
 * Tag-based flush degrades silently when the cache store doesn't support tags
 * (BACKEND-PHP §5 Cache Management) — entries still expire via their TTL.
 */
final readonly class PortfolioPublicFeedCache
{
    public const string PUBLIC_TAG = 'portfolios_public';

    private const int TTL_MINUTES = 5;

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public static function rememberPublic(string $key, callable $callback): mixed
    {
        $ttl = now()->addMinutes(self::TTL_MINUTES);

        try {
            return Cache::tags([self::PUBLIC_TAG])->remember($key, $ttl, $callback);
        } catch (Throwable) {
            return Cache::remember($key, $ttl, $callback);
        }
    }

    public static function flush(): void
    {
        try {
            Cache::tags([self::PUBLIC_TAG])->flush();
        } catch (Throwable) {
            // Store doesn't support tags — cached pages still expire via TTL.
        }
    }
}
