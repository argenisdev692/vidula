<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Application\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\AiResumeStudio\Application\DTOs\StudioFilterData;
use Modules\AiResumeStudio\Domain\Ports\JobSearchConfigRepositoryPort;
use Modules\AiResumeStudio\Domain\Ports\StudioRunRepositoryPort;
use Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models\JobSearchConfigEloquentModel;
use Modules\Cvs\Domain\Ports\CvRepositoryPort;

final readonly class ListStudioRunsHandler
{
    public function __construct(
        private StudioRunRepositoryPort $runs,
        private JobSearchConfigRepositoryPort $configs,
        private CvRepositoryPort $cvs,
    ) {}

    /**
     * @return array{
     *   runs: LengthAwarePaginator,
     *   configs: list<JobSearchConfigEloquentModel>,
     *   cvs: list<array{uuid: string, title: string, niche: string, is_primary: bool}>
     * }
     */
    public function handle(StudioFilterData $filters, int $userId, int $perPage = 15): array
    {
        return [
            'runs' => $this->runs->paginate($filters, $perPage),
            'configs' => $this->configs->recentForListing(20),
            'cvs' => $this->cvs->listSelectOptionsForUser($userId),
        ];
    }
}
