<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Application\Commands;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Modules\AiResumeStudio\Application\DTOs\SubmitStudioRunMetricsData;
use Modules\AiResumeStudio\Domain\Enums\StudioRunStatus;
use Modules\AiResumeStudio\Domain\Enums\StudioRunStep;
use Modules\AiResumeStudio\Domain\Ports\StudioRunDispatcherPort;
use Modules\AiResumeStudio\Domain\Ports\StudioRunRepositoryPort;
use Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models\StudioRunEloquentModel;

final readonly class SubmitStudioRunMetricsHandler
{
    public function __construct(
        private StudioRunRepositoryPort $runs,
        private StudioRunDispatcherPort $dispatcher,
    ) {}

    #[\NoDiscard]
    public function handle(string $uuid, SubmitStudioRunMetricsData $data, int $userId): StudioRunEloquentModel
    {
        $run = $this->runs->findByUuid($uuid)
          ?? throw (new ModelNotFoundException)->setModel(StudioRunEloquentModel::class, [$uuid]);

        if ((int) $run->user_id !== $userId) {
            throw (new ModelNotFoundException)->setModel(StudioRunEloquentModel::class, [$uuid]);
        }

        if ($run->status !== StudioRunStatus::AwaitingInput || $run->step !== StudioRunStep::AwaitingMetrics) {
            throw ValidationException::withMessages([
                'uuid' => __('This studio run is not waiting for metric answers.'),
            ]);
        }

        $meta = (array) ($run->meta ?? []);
        $audit = (array) ($meta['audit'] ?? []);
        $questions = array_values((array) ($audit['metric_questions'] ?? []));

        if (! $data->skipMetrics && $questions !== [] && ($data->metricAnswers === null || $data->metricAnswers === [])) {
            throw ValidationException::withMessages([
                'metric_answers' => __('Answer the metric questions or skip them to continue.'),
            ]);
        }

        $answers = [];
        foreach ($data->metricAnswers ?? [] as $row) {
            $id = trim((string) ($row['id'] ?? ''));
            $answer = trim((string) ($row['answer'] ?? ''));

            if ($id === '' || $answer === '') {
                continue;
            }

            $answers[] = [
                'id' => $id,
                'answer' => mb_substr($answer, 0, 2000),
            ];
        }

        $meta['metric_answers'] = $answers;
        $meta['skip_metrics'] = $data->skipMetrics;
        $meta['pipeline_phase'] = 'rewrite';

        $run = $this->runs->update($run, [
            'meta' => $meta,
            'status' => StudioRunStatus::Running->value,
            'step' => StudioRunStep::Refining->value,
            'error_summary' => null,
        ]);

        $this->dispatcher->dispatch($run->uuid);

        return $run;
    }
}
