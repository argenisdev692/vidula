<?php

declare(strict_types=1);

namespace Modules\VideoExport\Infrastructure\Queue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\VideoExport\Application\DTOs\EnqueueExportData;
use Modules\VideoExport\Application\Services\VideoExportJobStore;
use Modules\VideoExport\Infrastructure\Pipeline\VideoExportPipeline;
use Shared\Domain\Ports\AuditPort;
use Throwable;

#[Queue('video-export')]
#[Tries(1)]
#[Timeout(3600)]
final class ProcessVideoExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(
        private readonly EnqueueExportData $data,
        private readonly int|string $userId,
    ) {}

    public function handle(VideoExportPipeline $pipeline, AuditPort $audit): void
    {
        try {
            $result = $pipeline->run($this->data, $this->userId);
            $audit->log(
                'video_export.completed',
                null,
                [
                    'job_uuid' => $this->data->jobUuid,
                    'mode' => $this->data->mode,
                    'duration_seconds' => $result['duration_seconds'] ?? null,
                ],
                null,
                'video_export',
            );
        } catch (Throwable $e) {
            Log::error('video_export.job_failed', [
                'job_uuid' => $this->data->jobUuid,
                'error' => $e->getMessage(),
            ]);
            $audit->log(
                'video_export.failed',
                null,
                ['job_uuid' => $this->data->jobUuid, 'mode' => $this->data->mode],
                null,
                'video_export',
            );
            throw $e;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $message = $this->userFacingFailureMessage($exception);

        app(VideoExportJobStore::class)->markFailed(
            $this->data->jobUuid,
            $message,
        );
    }

    private function userFacingFailureMessage(?Throwable $exception): string
    {
        if ($exception === null) {
            return 'Export failed. Check Railway logs for video_export.job_failed.';
        }

        $raw = trim($exception->getMessage());

        // Common deploy gaps — surface clearly so the UI is usable without logs.
        if (str_contains(strtolower($raw), 'ffmpeg') || str_contains(strtolower($raw), 'ffprobe')) {
            return 'FFmpeg is missing or failed on the server. Redeploy with ffmpeg installed, then retry.';
        }

        if (str_contains(strtolower($raw), 'openai') || str_contains($raw, 'OPENAI_API_KEY')) {
            return 'AI step failed (OpenAI/Whisper). Check OPENAI_API_KEY on Railway.';
        }

        // Keep message short and free of paths/stack noise for the UI.
        $safe = preg_replace('/\s+/', ' ', $raw) ?? $raw;
        $safe = mb_substr($safe, 0, 240);

        return $safe !== ''
            ? 'Export failed: '.$safe
            : 'Export failed. Check Railway logs for video_export.job_failed.';
    }
}
