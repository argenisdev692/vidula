<?php

declare(strict_types=1);

namespace Modules\Portfolio\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Portfolio\Application\DTOs\AddPortfolioGalleryImageData;
use Modules\Portfolio\Domain\Ports\PortfolioRepositoryPort;
use Modules\Portfolio\Infrastructure\Cache\PortfolioPublicFeedCache;
use Modules\Portfolio\Infrastructure\Persistence\Eloquent\Models\PortfolioEloquentModel;
use Modules\Portfolio\Infrastructure\Persistence\Eloquent\Models\PortfolioMediaEloquentModel;
use Shared\Domain\Ports\StoragePort;

/**
 * Appends one image to a portfolio's gallery collection. Stored on R2 with
 * public visibility (same convention as cover/video) so the landing page
 * gallery can render it via a permanent URL. Authorization
 * (permission:UPDATE_PORTFOLIOS) is enforced at the route.
 */
final readonly class AddPortfolioGalleryImageHandler
{
    public function __construct(
        private PortfolioRepositoryPort $portfolios,
        private StoragePort $storage,
    ) {}

    #[\NoDiscard]
    public function handle(PortfolioEloquentModel $portfolio, AddPortfolioGalleryImageData $data): PortfolioMediaEloquentModel
    {
        // Upload happens before the transaction — object storage is not
        // transactional and must never run inside the DB unit-of-work.
        $path = $this->storage->putFile("portfolios/gallery/{$portfolio->uuid}", $data->image, 'public');
        $nextOrder = ((int) $portfolio->gallery()->max('sort_order')) + 1;

        $media = DB::transaction(fn () => $this->portfolios->addGalleryImage($portfolio, $path, $nextOrder));

        PortfolioPublicFeedCache::flush();

        return $media;
    }
}
