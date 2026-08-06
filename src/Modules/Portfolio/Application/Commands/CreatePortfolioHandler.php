<?php

declare(strict_types=1);

namespace Modules\Portfolio\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Portfolio\Application\DTOs\PortfolioData;
use Modules\Portfolio\Application\Support\PortfolioMediaKeyGuard;
use Modules\Portfolio\Application\Support\PortfolioPublicFeedCache;
use Modules\Portfolio\Domain\Ports\PortfolioRepositoryPort;
use Modules\Portfolio\Infrastructure\Persistence\Eloquent\Models\PortfolioEloquentModel;

/**
 * Persists a new portfolio project. Cover/video keys (when present) must already
 * exist on R2 from a prior browser → StoragePort::temporaryUploadUrl PUT.
 * Authorization (permission:CREATE_PORTFOLIOS) is enforced at the route.
 */
final readonly class CreatePortfolioHandler
{
    public function __construct(
        private PortfolioRepositoryPort $portfolios,
        private PortfolioMediaKeyGuard $mediaKeys,
    ) {}

    #[\NoDiscard]
    public function handle(PortfolioData $data, int $userId): PortfolioEloquentModel
    {
        $coverPath = $data->coverPath;
        $videoPath = $data->videoPath;

        if ($coverPath !== null) {
            $this->mediaKeys->assertValidCoverKey($coverPath);
        }

        if ($videoPath !== null) {
            $this->mediaKeys->assertValidVideoKey($videoPath);
        }

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
