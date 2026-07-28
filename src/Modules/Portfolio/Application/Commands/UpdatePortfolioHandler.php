<?php

declare(strict_types=1);

namespace Modules\Portfolio\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Portfolio\Application\DTOs\PortfolioData;
use Modules\Portfolio\Application\Support\PortfolioPublicFeedCache;
use Modules\Portfolio\Domain\Ports\PortfolioRepositoryPort;
use Modules\Portfolio\Infrastructure\Persistence\Eloquent\Models\PortfolioEloquentModel;
use Shared\Domain\Ports\StoragePort;

/**
 * Updates an existing portfolio project. A new cover/video replaces the previous
 * R2 object (the old key is deleted so the bucket never accumulates orphans);
 * `remove_cover`/`remove_video` clears a slot back to null (and deletes its R2
 * object) when no replacement file is uploaded — an explicit upload always wins
 * over a remove flag sent in the same request. When neither is set, the
 * existing column is left untouched. Authorization (permission:UPDATE_PORTFOLIOS)
 * is enforced at the route.
 */
final readonly class UpdatePortfolioHandler
{
    public function __construct(
        private PortfolioRepositoryPort $portfolios,
        private StoragePort $storage,
    ) {}

    #[\NoDiscard]
    public function handle(PortfolioEloquentModel $portfolio, PortfolioData $data): PortfolioEloquentModel
    {
        $attributes = [
            'title' => $data->title,
            'client_name' => $data->clientName,
            'project_type' => $data->projectType,
            'tech_stack' => $data->techStack,
            'live_url' => $data->liveUrl,
            'published_at' => $data->publishedAt,
            'is_public' => $data->isPublic,
            'description' => $data->description,
            'sort_order' => $data->sortOrder,
        ];

        $previousCover = null;
        $previousVideo = null;

        if ($data->cover !== null) {
            $previousCover = $portfolio->cover_path;
            // Upload the replacement before the transaction — object storage is
            // not transactional and must stay out of the DB unit-of-work.
            $attributes['cover_path'] = $this->storage->putFile('portfolios/cover', $data->cover, 'public');
        } elseif ($data->removeCover && $portfolio->cover_path !== null) {
            $previousCover = $portfolio->cover_path;
            $attributes['cover_path'] = null;
        }

        if ($data->video !== null) {
            $previousVideo = $portfolio->video_path;
            $attributes['video_path'] = $this->storage->putFile('portfolios/video', $data->video, 'public');
        } elseif ($data->removeVideo && $portfolio->video_path !== null) {
            $previousVideo = $portfolio->video_path;
            $attributes['video_path'] = null;
        }

        $updated = DB::transaction(fn () => $this->portfolios->update($portfolio, $attributes));

        // Delete superseded objects only after the write commits, so a failed
        // update never orphans the row from its still-referenced assets.
        if (is_string($previousCover) && $previousCover !== '' && $previousCover !== $updated->cover_path) {
            $this->storage->delete($previousCover);
        }

        if (is_string($previousVideo) && $previousVideo !== '' && $previousVideo !== $updated->video_path) {
            $this->storage->delete($previousVideo);
        }

        PortfolioPublicFeedCache::flush();

        return $updated;
    }
}
