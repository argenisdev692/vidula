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
 * (BACKEND-PHP §5 Cache Management). A monotonic version key always bumps on
 * flush so plain-key fallbacks (e.g. CACHE_STORE=array in tests) invalidate too.
 *
 * Cached payloads MUST be plain arrays — never Eloquent models / paginators.
 * Serializing models + R2 accessors across Redis caused sticky 500s after the
 * first warm (same class of bug as blog categories / services).
 */
final readonly class PortfolioPublicFeedCache
{
    public const string PUBLIC_TAG = 'portfolios_public';

    private const string VERSION_KEY = 'portfolios.public.cache_version';

    private const int TTL_MINUTES = 5;

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public static function rememberPublic(string $key, callable $callback): mixed
    {
        $version = (int) Cache::get(self::VERSION_KEY, 1);
        $versionedKey = "{$key}.v{$version}";
        $ttl = now()->addMinutes(self::TTL_MINUTES);

        try {
            return Cache::tags([self::PUBLIC_TAG])->remember($versionedKey, $ttl, $callback);
        } catch (Throwable) {
            return Cache::remember($versionedKey, $ttl, $callback);
        }
    }

    public static function flush(): void
    {
        try {
            Cache::tags([self::PUBLIC_TAG])->flush();
        } catch (Throwable) {
            // Store doesn't support tags — version bump below still invalidates.
        }

        Cache::forever(self::VERSION_KEY, ((int) Cache::get(self::VERSION_KEY, 1)) + 1);
    }
}
