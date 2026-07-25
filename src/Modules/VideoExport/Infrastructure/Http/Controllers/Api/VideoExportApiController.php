<?php

declare(strict_types=1);

namespace Modules\VideoExport\Infrastructure\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\VideoExport\Application\Commands\EnqueueExportHandler;
use Modules\VideoExport\Application\Commands\PresignUploadHandler;
use Modules\VideoExport\Application\DTOs\EnqueueExportData;
use Modules\VideoExport\Application\DTOs\PresignUploadData;
use Modules\VideoExport\Application\Queries\GetJobStatusHandler;
use Modules\VideoExport\Infrastructure\Http\Controllers\VideoExportController;
use RuntimeException;

/**
 * Sanctum secondary surface for Video Export (mobile / external clients).
 * Reuses the same handlers as {@see VideoExportController}. Authorization via
 * `hasPermissionTo` so it is safe under the `sanctum` guard. Documented by
 * Scramble via return types + `auth:sanctum` — no manual annotations.
 */
final readonly class VideoExportApiController
{
    public function __construct(
        private PresignUploadHandler $presign,
        private EnqueueExportHandler $enqueue,
        private GetJobStatusHandler $status,
    ) {}

    /**
     * Presign a direct-to-R2 upload.
     *
     * Returns a short-lived PUT URL so the client uploads video/script bytes
     * straight to object storage (never through the app server).
     */
    public function presign(PresignUploadData $data, Request $request): JsonResponse
    {
        abort_unless((bool) $request->user()?->hasPermissionTo('CREATE_VIDEO_EXPORTS'), 403);

        /** @var User $user */
        $user = $request->user();

        return response()->json(['data' => $this->presign->handle($data, $user->id)]);
    }

    /**
     * Enqueue a video export job.
     *
     * Modes: `merge` | `clean` | `ai`. Returns 202 with `job_uuid` for polling.
     * `ai_provider` selects Gemini/OpenAI/Anthropic for optional script review;
     * speech timestamps always use OpenAI Whisper when AI/script runs.
     */
    public function store(EnqueueExportData $data, Request $request): JsonResponse
    {
        abort_unless((bool) $request->user()?->hasPermissionTo('CREATE_VIDEO_EXPORTS'), 403);

        /** @var User $user */
        $user = $request->user();

        try {
            $result = $this->enqueue->handle($data, $user->id);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $result], 202);
    }

    /**
     * Poll export job status.
     *
     * Owner-scoped: another user's UUID resolves as `not_found`.
     */
    public function jobStatus(string $job_uuid, Request $request): JsonResponse
    {
        abort_unless((bool) $request->user()?->hasPermissionTo('VIEW_ANY_VIDEO_EXPORTS'), 403);

        /** @var User $user */
        $user = $request->user();

        return response()->json(['data' => $this->status->handle($job_uuid, $user->id)]);
    }
}
