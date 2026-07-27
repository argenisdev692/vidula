<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Application\Commands;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\AiResumeStudio\Application\DTOs\JobSearchConfigData;
use Modules\AiResumeStudio\Domain\Ports\JobSearchConfigRepositoryPort;
use Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models\JobSearchConfigEloquentModel;
use Modules\Cvs\Domain\Ports\CvRepositoryPort;

final readonly class CreateJobSearchConfigHandler
{
    public function __construct(
        private CvRepositoryPort $cvs,
        private JobSearchConfigRepositoryPort $configs,
    ) {}

    #[\NoDiscard]
    public function handle(JobSearchConfigData $data, int $userId): JobSearchConfigEloquentModel
    {
        $cv = $this->cvs->findByUuid($data->cvUuid);

        if ($cv === null || $cv->user_id !== $userId) {
            throw ValidationException::withMessages(['cv_uuid' => __('CV not found.')]);
        }

        return DB::transaction(fn (): JobSearchConfigEloquentModel => $this->configs->create([
            'user_id' => $userId,
            'cv_id' => $cv->id,
            'mode' => $data->mode,
            'keywords' => $data->keywords |> trim(...),
            'location_scope' => $data->locationScope,
            'search_language' => $data->searchLanguage,
            'resume_language' => $data->resumeLanguage,
            'targeting_prompt' => $data->targetingPrompt,
            'schedule_enabled' => $data->scheduleEnabled,
            'deep_extract_enabled' => $data->deepExtractEnabled,
            'auto_send_enabled' => $data->autoSendEnabled,
            'provider' => $data->provider,
            'status' => $data->status,
        ]));
    }
}
