<?php

declare(strict_types=1);

namespace Modules\Services\Application\Queries;

use Closure;
use Illuminate\Support\Facades\Cache;
use Modules\Services\Application\ReadModels\ServicePublicReadModel;
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
 * a single `<select>`, so a plain array is the honest return type
 * (YAGNI: no pagination machinery for a handful of rows).
 *
 * Cached payload is mapped {@see ServicePublicReadModel} arrays — never Eloquent
 * models (same rule as blog categories; avoids sticky cache payloads).
 */
final readonly class ListPublicServicesHandler
{
    private const string CACHE_KEY = 'services.public.active.v2';

    private const int TTL_MINUTES = 15;

    public function __construct(private ServiceRepositoryPort $services) {}

    /**
     * @return list<array{uuid: string, name: string, slug: string, description: string|null, sort_order: int}>
     */
    public function handle(): array
    {
        return $this->remember(
            function (): array {
                return $this->services
                    ->listActive()
                    ->map(static function (ServiceEloquentModel $model): array {
                        return ServicePublicReadModel::fromModel($model)->toArray();
                    })
                    ->values()
                    ->all();
            },
        );
    }

    /**
     * @param  Closure(): list<array{uuid: string, name: string, slug: string, description: string|null, sort_order: int}>  $callback
     * @return list<array{uuid: string, name: string, slug: string, description: string|null, sort_order: int}>
     */
    private function remember(Closure $callback): array
    {
        try {
            /** @var list<array{uuid: string, name: string, slug: string, description: string|null, sort_order: int}> */
            return Cache::tags(['services_public'])->remember(
                self::CACHE_KEY,
                now()->addMinutes(self::TTL_MINUTES),
                $callback,
            );
        } catch (Throwable) {
            /** @var list<array{uuid: string, name: string, slug: string, description: string|null, sort_order: int}> */
            return Cache::remember(
                self::CACHE_KEY,
                now()->addMinutes(self::TTL_MINUTES),
                $callback,
            );
        }
    }
}
