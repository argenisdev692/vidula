<?php

declare(strict_types=1);

namespace Modules\Portfolio\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Portfolio\Application\DTOs\PortfolioData;
use Modules\Portfolio\Domain\Ports\PortfolioRepositoryPort;
use Modules\Portfolio\Infrastructure\Cache\PortfolioPublicFeedCache;
use Modules\Portfolio\Infrastructure\Persistence\Eloquent\Models\PortfolioEloquentModel;
use Shared\Domain\Ports\StoragePort;

/**
 * Persists a new portfolio project. Cover and video, when uploaded, are stored
 * on R2 with public visibility and the returned object keys are written to the
 * `cover_path` / `video_path` columns. Authorization (permission:CREATE_PORTFOLIOS)
 * is enforced at the route — never inside this handler.
 */
final readonly class CreatePortfolioHandler
{
    public function __construct(
        private PortfolioRepositoryPort $portfolios,
        private StoragePort $storage,
    ) {}

    #[\NoDiscard]
    public function handle(PortfolioData $data, int $userId): PortfolioEloquentModel
    {
        // Uploads happen before the transaction — object storage is not
        // transactional and must never run inside the DB unit-of-work.
        $coverPath = $data->cover !== null
            ? $this->storage->putFile('portfolios/cover', $data->cover, 'public')
            : null;

        $videoPath = $data->video !== null
            ? $this->storage->putFile('portfolios/video', $data->video, 'public')
            : null;

        $portfolio = DB::transaction(fn () => $this->portfolios->create([
            'title' => $data->title,
            'client_name' => $data->clientName,
            'project_type' => $data->projectType,
            'tech_stack' => $data->techStack,
            'live_url' => $data->liveUrl,
            'published_at' => $data->publishedAt,
            'is_public' => $data->isPublic,
            'cover_path' => $coverPath,
            'video_path' => $videoPath,
            'description' => $data->description,
            'sort_order' => $data->sortOrder,
            'user_id' => $userId,
        ]));

        PortfolioPublicFeedCache::flush();

        return $portfolio;
    }
}
