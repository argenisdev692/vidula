<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Application\DTOs;

use Illuminate\Validation\Rule;
use Modules\AiResumeStudio\Domain\Enums\LocationScope;
use Modules\AiResumeStudio\Domain\Enums\ResumeLanguage;
use Modules\AiResumeStudio\Domain\Enums\SearchLanguage;
use Modules\AiResumeStudio\Domain\Enums\StudioMode;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
final class StartStudioRunData extends Data
{
    /**
     * @param  list<string>|null  $githubSelectedRepos
     */
    public function __construct(
        public string $cvUuid,
        public string $mode = 'career',
        public string $provider = 'openai',
        public ?string $keywords = null,
        public ?string $targetingPrompt = null,
        public ?string $githubUsername = null,
        public ?array $githubSelectedRepos = null,
        public ?string $githubExtraPrompt = null,
        public bool $deepExtract = false,
        public ?string $targetJobTitle = null,
        public ?string $locationScope = 'remote',
        public string $searchLanguage = 'both',
        public string $resumeLanguage = 'en',
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'cv_uuid' => ['required', 'uuid'],
            'mode' => ['required', 'string', Rule::in(StudioMode::values())],
            'provider' => ['required', 'string', 'in:openai,anthropic,gemini'],
            'keywords' => ['nullable', 'string', 'max:500'],
            'targeting_prompt' => ['nullable', 'string', 'max:5000'],
            'github_username' => ['nullable', 'string', 'max:255'],
            'github_selected_repos' => ['nullable', 'array', 'max:20'],
            'github_selected_repos.*' => ['string', 'max:255'],
            'github_extra_prompt' => ['nullable', 'string', 'max:5000'],
            'deep_extract' => ['boolean'],
            'target_job_title' => ['nullable', 'string', 'max:255'],
            'location_scope' => ['nullable', 'string', Rule::in(LocationScope::values())],
            'search_language' => ['required', 'string', Rule::in(SearchLanguage::values())],
            'resume_language' => ['required', 'string', Rule::in(ResumeLanguage::values())],
        ];
    }
}
