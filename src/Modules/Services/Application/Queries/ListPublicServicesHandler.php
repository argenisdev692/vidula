<?php

declare(strict_types=1);

namespace Modules\Services\Application\Queries;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Services\Domain\Ports\ServiceRepositoryPort;
use Modules\Services\Infrastructure\Cache\ServicePublicFeedCache;
use Modules\Services\Infrastructure\Persistence\Eloquent\Models\ServiceEloquentModel;
use Throwable;

/**
 * Landing-page select-input feed: `is_active` services only, no authentication
 * required. Kept as its own query (rather than reusing
 * {@see ListServicesHandler}) since the trust boundary and column allowlist
 * genuinely diverge — this one is reachable by anonymous internet traffic
 * (BACKEND-PHP §7 Insecure Design).
 *
 * Cached: this is the one query hit by anonymous traffic with no per-user
 * throttle (BACKEND-PHP §5 Cache Management). Every
 * Create/Update/Delete/Restore/Bulk* handler busts the `services_public` tag
 * via {@see ServicePublicFeedCache}.
 * Not paginated — the whole list is a bounded, small catalog meant to populate
 * a single `<select>`, so a plain `Collection` is the honest return type
 * (YAGNI: no pagination machinery for a handful of rows).
 */
final readonly class ListPublicServicesHandler
{
    private const string CACHE_KEY = 'services.public.active';

    private const int TTL_MINUTES = 15;

    public function __construct(private ServiceRepositoryPort $services) {}

    /**
     * @return Collection<int, ServiceEloquentModel>
     */
    public function handle(): Collection
    {
        try {
            return Cache::tags(['services_public'])->remember(
                self::CACHE_KEY,
                now()->addMinutes(self::TTL_MINUTES),
                fn () => $this->services->listActive(),
            );
        } catch (Throwable) {
            return Cache::remember(
                self::CACHE_KEY,
                now()->addMinutes(self::TTL_MINUTES),
                fn () => $this->services->listActive(),
            );
        }
    }
}
