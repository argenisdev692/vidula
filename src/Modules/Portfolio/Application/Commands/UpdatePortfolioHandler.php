<?php

declare(strict_types=1);

namespace Modules\Portfolio\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Portfolio\Application\DTOs\PortfolioData;
use Modules\Portfolio\Application\Support\PortfolioMediaKeyGuard;
use Modules\Portfolio\Application\Support\PortfolioPublicFeedCache;
use Modules\Portfolio\Domain\Ports\PortfolioRepositoryPort;
use Modules\Portfolio\Infrastructure\Persistence\Eloquent\Models\PortfolioEloquentModel;
use Shared\Domain\Ports\StoragePort;

/**
 * Updates an existing portfolio project. A new cover/video R2 key replaces the
 * previous object (old key deleted after commit); `remove_cover`/`remove_video`
 * clears a slot when no replacement key is sent — an explicit key always wins
 * over a remove flag. Authorization (permission:UPDATE_PORTFOLIOS) is
 * enforced at the route.
 */
final readonly class UpdatePortfolioHandler
{
    public function __construct(
        private PortfolioRepositoryPort $portfolios,
        private StoragePort $storage,
        private PortfolioMediaKeyGuard $mediaKeys,
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

        if ($data->coverPath !== null) {
            $this->mediaKeys->assertValidCoverKey($data->coverPath);
            $previousCover = $portfolio->cover_path;
            $attributes['cover_path'] = $data->coverPath;
        } elseif ($data->removeCover && $portfolio->cover_path !== null) {
            $previousCover = $portfolio->cover_path;
            $attributes['cover_path'] = null;
        }

        if ($data->videoPath !== null) {
            $this->mediaKeys->assertValidVideoKey($data->videoPath);
            $previousVideo = $portfolio->video_path;
            $attributes['video_path'] = $data->videoPath;
        } elseif ($data->removeVideo && $portfolio->video_path !== null) {
            $previousVideo = $portfolio->video_path;
            $attributes['video_path'] = null;
        }

        $updated = DB::transaction(fn () => $this->portfolios->update($portfolio, $attributes));

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
