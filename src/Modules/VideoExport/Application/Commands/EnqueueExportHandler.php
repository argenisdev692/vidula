<?php

declare(strict_types=1);

namespace Modules\VideoExport\Application\Commands;

use Modules\VideoExport\Application\DTOs\EnqueueExportData;
use Modules\VideoExport\Application\Services\VideoExportJobStore;
use Modules\VideoExport\Domain\Enums\ExportMode;
use Modules\VideoExport\Infrastructure\Pipeline\InputResolver;
use Modules\VideoExport\Infrastructure\Pipeline\OpenAiWhisperTranscriber;
use Modules\VideoExport\Infrastructure\Queue\ProcessVideoExportJob;
use RuntimeException;
use Shared\Domain\Ports\AuditPort;

final readonly class EnqueueExportHandler
{
    public function __construct(
        private VideoExportJobStore $jobs,
        private InputResolver $resolver,
        private OpenAiWhisperTranscriber $whisper,
        private AuditPort $audit,
    ) {}

    /**
     * @return array{job_uuid: string, status: 'queued'|'duplicate'}
     */
    #[\NoDiscard]
    public function handle(EnqueueExportData $data, int|string $userId): array
    {
        if ($this->jobs->exists($data->jobUuid)) {
            return ['job_uuid' => $data->jobUuid, 'status' => 'duplicate'];
        }

        foreach ($data->videoPaths as $path) {
            $this->resolver->assertSafeUrl($path);
        }
        if (filled($data->scriptPath)) {
            $this->resolver->assertSafeUrl((string) $data->scriptPath);
        }

        $mode = $data->exportMode();
        if (($mode->usesSpeechAi() || filled($data->scriptPath)) && ! $this->whisper->isConfigured()) {
            throw new RuntimeException('AI cleaning requires OPENAI_API_KEY.');
        }

        if ($mode === ExportMode::Ai && filled($data->scriptPath) === false) {
            // Script optional — allowed.
        }

        $this->jobs->put([
            'job_uuid' => $data->jobUuid,
            'user_id' => $userId,
            'status' => 'queued',
            'mode' => $data->mode,
            'result' => null,
            'error' => null,
            'updated_at' => now()->toIso8601String(),
        ]);

        ProcessVideoExportJob::dispatch($data, $userId);

        $this->audit->log(
            'video_export.queued',
            null,
            [
                'job_uuid' => $data->jobUuid,
                'mode' => $data->mode,
                'source_count' => count($data->videoPaths),
            ],
            null,
            'video_export',
        );

        return ['job_uuid' => $data->jobUuid, 'status' => 'queued'];
    }
}
