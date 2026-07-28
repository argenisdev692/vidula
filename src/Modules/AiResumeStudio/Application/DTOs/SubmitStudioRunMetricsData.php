<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Application\DTOs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
final class SubmitStudioRunMetricsData extends Data
{
    /**
     * @param  list<array{id?: string, answer?: string}>|null  $metricAnswers
     */
    public function __construct(
        public ?array $metricAnswers = null,
        public bool $skipMetrics = false,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'metric_answers' => ['nullable', 'array', 'max:12'],
            'metric_answers.*.id' => ['required_with:metric_answers', 'string', 'max:64'],
            'metric_answers.*.answer' => ['nullable', 'string', 'max:2000'],
            'skip_metrics' => ['boolean'],
        ];
    }
}
