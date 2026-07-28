<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Application\Commands;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\AiResumeStudio\Application\DTOs\StartStudioRunData;
use Modules\AiResumeStudio\Domain\Enums\StudioMode;
use Modules\AiResumeStudio\Domain\Enums\StudioRunStatus;
use Modules\AiResumeStudio\Domain\Enums\StudioRunStep;
use Modules\AiResumeStudio\Domain\Ports\GithubEnrichmentRepositoryPort;
use Modules\AiResumeStudio\Domain\Ports\JobSearchConfigRepositoryPort;
use Modules\AiResumeStudio\Domain\Ports\StudioRunDispatcherPort;
use Modules\AiResumeStudio\Domain\Ports\StudioRunRepositoryPort;
use Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models\JobSearchConfigEloquentModel;
use Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models\StudioRunEloquentModel;
use Modules\Cvs\Domain\Ports\CvRepositoryPort;

final readonly class StartStudioRunHandler
{
    public function __construct(
        private CvRepositoryPort $cvs,
        private StudioRunRepositoryPort $runs,
        private JobSearchConfigRepositoryPort $configs,
        private GithubEnrichmentRepositoryPort $githubEnrichments,
        private StudioRunDispatcherPort $dispatcher,
    ) {}

    #[\NoDiscard]
    public function handle(StartStudioRunData $data, int $userId): StudioRunEloquentModel
    {
        $cv = $this->cvs->findByUuid($data->cvUuid);

        if ($cv === null || $cv->user_id !== $userId) {
            throw ValidationException::withMessages(['cv_uuid' => __('CV not found.')]);
        }

        if ($data->mode === StudioMode::Other->value && blank($data->targetingPrompt)) {
            throw ValidationException::withMessages([
                'targeting_prompt' => __('Targeting prompt is required for other-niche mode.'),
            ]);
        }

        $run = DB::transaction(function () use ($data, $userId, $cv): StudioRunEloquentModel {
            $keywords = $data->keywords !== null && $data->keywords !== ''
              ? ($data->keywords |> trim(...))
              : ($data->targetJobTitle !== null && $data->targetJobTitle !== ''
                ? ($data->targetJobTitle |> trim(...))
                : 'jobs');

            $config = $this->configs->create([
                'user_id' => $userId,
                'cv_id' => $cv->id,
                'mode' => $data->mode,
                'keywords' => $keywords,
                'location_scope' => $data->locationScope,
                'search_language' => $data->searchLanguage,
                'resume_language' => $data->resumeLanguage,
                'targeting_prompt' => $data->targetingPrompt,
                'schedule_enabled' => false,
                'deep_extract_enabled' => $data->deepExtract,
                'auto_send_enabled' => false,
                'provider' => $data->provider,
            ]);

            if (
                $data->mode === StudioMode::Career->value
                && $data->githubUsername !== null
                && $data->githubUsername !== ''
            ) {
                $this->githubEnrichments->create([
                    'user_id' => $userId,
                    'cv_id' => $cv->id,
                    'github_username' => $data->githubUsername |> trim(...),
                    'selected_repos' => $data->githubSelectedRepos ?? [],
                    'extra_prompt' => $data->githubExtraPrompt,
                ]);
            }

            return $this->runs->create([
                'user_id' => $userId,
                'cv_id' => $cv->id,
                'job_search_config_id' => $config->id,
                'mode' => $data->mode,
                'step' => StudioRunStep::Queued->value,
                'status' => StudioRunStatus::Pending->value,
                'meta' => [
                    'provider' => $data->provider,
                    'keywords' => $keywords,
                    'targeting_prompt' => $data->targetingPrompt,
                    'deep_extract' => $data->deepExtract,
                    'target_job_title' => $data->targetJobTitle,
                    'job_description' => $data->jobDescription,
                    'location_scope' => $data->locationScope,
                    'search_language' => $data->searchLanguage,
                    'resume_language' => $data->resumeLanguage,
                    'github_username' => $data->githubUsername,
                    'github_selected_repos' => $data->githubSelectedRepos,
                    'github_extra_prompt' => $data->githubExtraPrompt,
                    'pipeline_phase' => 'judge',
                ],
            ]);
        });

        $this->dispatcher->dispatch($run->uuid);

        return $run;
    }

    public function handleFromConfig(JobSearchConfigEloquentModel $config): StudioRunEloquentModel
    {
        $run = $this->runs->create([
            'user_id' => $config->user_id,
            'cv_id' => $config->cv_id,
            'job_search_config_id' => $config->id,
            'mode' => $config->mode->value,
            'step' => StudioRunStep::Queued->value,
            'status' => StudioRunStatus::Pending->value,
            'meta' => [
                'provider' => $config->provider,
                'keywords' => $config->keywords,
                'targeting_prompt' => $config->targeting_prompt,
                'deep_extract' => $config->deep_extract_enabled,
                'location_scope' => $config->location_scope,
                'search_language' => $config->search_language,
                'resume_language' => $config->resume_language ?? 'en',
                'scheduled' => true,
                'pipeline_phase' => 'judge',
            ],
        ]);

        $this->dispatcher->dispatch($run->uuid);

        return $run;
    }
}
