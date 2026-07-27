<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Application\DTOs;

use Illuminate\Validation\Rule;
use Modules\AiResumeStudio\Domain\Enums\JobSearchConfigStatus;
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
final class JobSearchConfigData extends Data
{
    public function __construct(
        public string $cvUuid,
        public string $mode,
        public string $keywords,
        public ?string $targetingPrompt = null,
        public ?string $locationScope = 'remote',
        public string $searchLanguage = 'both',
        public string $resumeLanguage = 'en',
        public bool $scheduleEnabled = false,
        public bool $deepExtractEnabled = false,
        public bool $autoSendEnabled = false,
        public string $provider = 'openai',
        public string $status = 'active',
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'cv_uuid' => ['required', 'uuid'],
            'mode' => ['required', 'string', Rule::in(StudioMode::values())],
            'keywords' => ['required', 'string', 'max:500'],
            'targeting_prompt' => ['nullable', 'string', 'max:5000'],
            'location_scope' => ['nullable', 'string', Rule::in(LocationScope::values())],
            'search_language' => ['required', 'string', Rule::in(SearchLanguage::values())],
            'resume_language' => ['required', 'string', Rule::in(ResumeLanguage::values())],
            'schedule_enabled' => ['boolean'],
            'deep_extract_enabled' => ['boolean'],
            'auto_send_enabled' => ['boolean'],
            'provider' => ['required', 'string', 'in:openai,anthropic,gemini'],
            'status' => ['required', 'string', Rule::enum(JobSearchConfigStatus::class)],
        ];
    }
}
