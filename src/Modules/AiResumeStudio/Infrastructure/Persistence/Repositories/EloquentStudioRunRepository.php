<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Infrastructure\Persistence\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\AiResumeStudio\Application\DTOs\StudioFilterData;
use Modules\AiResumeStudio\Domain\Ports\StudioRunRepositoryPort;
use Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models\StudioRunEloquentModel;

final class EloquentStudioRunRepository implements StudioRunRepositoryPort
{
    public function paginate(StudioFilterData $filters, int $perPage): LengthAwarePaginator
    {
        return StudioRunEloquentModel::query()
            ->when($filters->status === 'suspended', fn ($q) => $q->onlyTrashed())
            ->applyFilters($filters)
            ->with([
                'cv:id,uuid,title,niche',
                'jobSearchConfig:id,uuid,keywords',
            ])
            ->select([
                'id',
                'uuid',
                'user_id',
                'cv_id',
                'job_search_config_id',
                'mode',
                'step',
                'status',
                'error_summary',
                'started_at',
                'finished_at',
                'created_at',
                'deleted_at',
            ])
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findByUuid(string $uuid): ?StudioRunEloquentModel
    {
        return StudioRunEloquentModel::withTrashed()
            ->with([
                'cv:id,uuid,title,niche,raw_text',
                'jobSearchConfig:id,uuid,keywords,schedule_enabled,deep_extract_enabled,auto_send_enabled',
                'refinedCvs:id,uuid,studio_run_id,ats_score,target_job_title,refined_md,feedback,version,provider,created_at',
                'jobMatches:id,uuid,studio_run_id,job_title,company_name,job_url,match_score,match_reasoning,application_status,source,created_at,deleted_at',
                'outreachDrafts:id,uuid,studio_run_id,kind,subject,body,language,status,created_at',
            ])
            ->where('uuid', $uuid)
            ->first();
    }

    public function create(array $attributes): StudioRunEloquentModel
    {
        return StudioRunEloquentModel::query()->create($attributes);
    }

    public function update(StudioRunEloquentModel $run, array $attributes): StudioRunEloquentModel
    {
        $run->update($attributes);

        return $run->refresh();
    }
}
