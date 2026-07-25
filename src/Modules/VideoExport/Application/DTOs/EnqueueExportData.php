<?php

declare(strict_types=1);

namespace Modules\VideoExport\Application\DTOs;

use Illuminate\Validation\Rule;
use Modules\VideoExport\Domain\Enums\ExportMode;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
final class EnqueueExportData extends Data
{
    /**
     * @param  list<string>  $videoPaths
     */
    public function __construct(
        public string $jobUuid,
        public string $mode,
        public array $videoPaths,
        public int $silenceThresholdSeconds = 1,
        public bool $audioEnhancementEnabled = true,
        public bool $sortByCreationTime = true,
        public string $language = 'es',
        public string $aiProvider = 'gemini',
        public ?string $scriptPath = null,
        public ?string $scriptFormat = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        $max = (int) config('video-export.max_source_videos', 50);

        return [
            'job_uuid' => ['required', 'uuid'],
            'mode' => ['required', 'string', Rule::enum(ExportMode::class)],
            'video_paths' => ['required', 'array', 'min:1', 'max:'.$max],
            'video_paths.*' => ['required', 'string', 'max:2048'],
            'silence_threshold_seconds' => ['sometimes', 'integer', 'min:1', 'max:3'],
            'audio_enhancement_enabled' => ['sometimes', 'boolean'],
            'sort_by_creation_time' => ['sometimes', 'boolean'],
            'language' => ['sometimes', 'string', 'min:2', 'max:8'],
            'ai_provider' => ['sometimes', 'string', Rule::in(['openai', 'anthropic', 'gemini'])],
            'script_path' => ['nullable', 'string', 'max:2048'],
            'script_format' => ['nullable', 'string', Rule::in(['markdown', 'pdf'])],
        ];
    }

    public function exportMode(): ExportMode
    {
        return ExportMode::from($this->mode);
    }
}
