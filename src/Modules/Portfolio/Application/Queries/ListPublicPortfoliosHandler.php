<?php

declare(strict_types=1);

namespace Modules\Portfolio\Application\Queries;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Portfolio\Application\ReadModels\PortfolioPublicReadModel;
use Modules\Portfolio\Application\Support\PortfolioPublicFeedCache;
use Modules\Portfolio\Domain\Ports\PortfolioRepositoryPort;
use Modules\Portfolio\Infrastructure\Persistence\Eloquent\Models\PortfolioEloquentModel;
use Spatie\LaravelData\PaginatedDataCollection;

/**
 * Landing-page feed: `is_public` portfolios only, no authentication required.
 * Kept as its own query (rather than reusing {@see ListPortfoliosHandler})
 * since the trust boundary and column allowlist genuinely diverge — this one
 * is reachable by anonymous internet traffic (BACKEND-PHP §7 Insecure Design).
 *
 * Cached, unlike the admin `ListPortfoliosHandler`/`GetPortfolioHandler`: this
 * is the one query hit by anonymous traffic with no per-user throttle, so
 * caching earns its complexity here specifically (BACKEND-PHP §5 Cache
 * Management). Every Create/Update/Delete/Restore/Bulk/Gallery handler busts
 * the `portfolios_public` tag via {@see PortfolioPublicFeedCache}.
 *
 * Important: we cache already-mapped public arrays (+ pagination meta) — never
 * Eloquent models. Serializing models + accessors (R2) across Redis was a
 * source of sticky 500s after the first warm (same fix as blog categories).
 */
final readonly class ListPublicPortfoliosHandler
{
    public function __construct(private PortfolioRepositoryPort $portfolios) {}

    /**
     * @return PaginatedDataCollection<int, PortfolioPublicReadModel>
     */
    public function handle(int $perPage = 15): PaginatedDataCollection
    {
        $page = max((int) request()->integer('page', 1), 1);
        $cacheKey = "portfolios.public.page.{$page}.per_page.{$perPage}";

        /** @var array{items: list<array<string, mixed>>, total: int, per_page: int, current_page: int, path: string} $payload */
        $payload = PortfolioPublicFeedCache::rememberPublic(
            $cacheKey,
            function () use ($perPage): array {
                $paginator = $this->portfolios->paginatePublic($perPage);

                /** @var list<array<string, mixed>> $items */
                $items = $paginator->getCollection()
                    ->map(static function (PortfolioEloquentModel $model): array {
                        return PortfolioPublicReadModel::fromModel($model)->toArray();
                    })
                    ->values()
                    ->all();

                return [
                    'items' => $items,
                    'total' => $paginator->total(),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'path' => $paginator->path(),
                ];
            },
        );

        $paginator = new LengthAwarePaginator(
            $payload['items'],
            $payload['total'],
            $payload['per_page'],
            $payload['current_page'],
            [
                'path' => $payload['path'],
                'query' => request()->query(),
            ],
        );

        return PortfolioPublicReadModel::collect($paginator, PaginatedDataCollection::class);
    }
}
