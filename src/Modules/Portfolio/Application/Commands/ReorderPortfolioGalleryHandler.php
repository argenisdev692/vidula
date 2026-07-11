<?php

declare(strict_types=1);

namespace Modules\Portfolio\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Portfolio\Domain\Ports\PortfolioRepositoryPort;
use Modules\Portfolio\Infrastructure\Cache\PortfolioPublicFeedCache;
use Modules\Portfolio\Infrastructure\Persistence\Eloquent\Models\PortfolioEloquentModel;
use Shared\Application\DTOs\BulkUuidsData;

/**
 * Re-applies gallery display order from a drag-and-drop reorder on the frontend.
 * Reuses {@see BulkUuidsData} (DRY) — the array's position, not its content,
 * carries the new `sort_order`. Authorization (permission:UPDATE_PORTFOLIOS) is
 * enforced at the route.
 */
final readonly class ReorderPortfolioGalleryHandler
{
    public function __construct(private PortfolioRepositoryPort $portfolios) {}

    public function handle(PortfolioEloquentModel $portfolio, BulkUuidsData $data): void
    {
        DB::transaction(fn () => $this->portfolios->reorderGallery($portfolio, $data->uuids));

        PortfolioPublicFeedCache::flush();
    }
}
