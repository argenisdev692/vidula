<?php

declare(strict_types=1);

namespace Modules\Portfolio\Application\Commands;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Modules\Portfolio\Domain\Ports\PortfolioRepositoryPort;
use Modules\Portfolio\Infrastructure\Cache\PortfolioPublicFeedCache;
use Modules\Portfolio\Infrastructure\Persistence\Eloquent\Models\PortfolioEloquentModel;
use Modules\Portfolio\Infrastructure\Persistence\Eloquent\Models\PortfolioMediaEloquentModel;
use Shared\Domain\Ports\StoragePort;

/**
 * Removes one gallery image: deletes the R2 object and the row (a gallery
 * image is a leaf asset — unlike the portfolio aggregate itself, it is not
 * soft-restorable). Authorization (permission:UPDATE_PORTFOLIOS) is enforced
 * at the route.
 */
final readonly class DeletePortfolioGalleryImageHandler
{
    public function __construct(
        private PortfolioRepositoryPort $portfolios,
        private StoragePort $storage,
    ) {}

    public function handle(PortfolioEloquentModel $portfolio, string $mediaUuid): bool
    {
        $media = $portfolio->gallery()->where('uuid', $mediaUuid)->first()
            ?? throw (new ModelNotFoundException)->setModel(PortfolioMediaEloquentModel::class, [$mediaUuid]);

        $path = $media->path;

        $deleted = DB::transaction(fn () => $this->portfolios->deleteGalleryImage($portfolio, $mediaUuid));

        // Delete the object only after the row is removed and committed, so a
        // failed delete never loses a still-referenced image.
        if ($deleted) {
            $this->storage->delete($path);
        }

        PortfolioPublicFeedCache::flush();

        return $deleted;
    }
}
