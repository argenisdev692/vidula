<?php

declare(strict_types=1);

namespace Modules\VideoExport\Infrastructure\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\VideoExport\Application\Commands\EnqueueExportHandler;
use Modules\VideoExport\Application\Commands\PresignUploadHandler;
use Modules\VideoExport\Application\DTOs\EnqueueExportData;
use Modules\VideoExport\Application\DTOs\PresignUploadData;
use Modules\VideoExport\Application\Queries\GetJobStatusHandler;
use RuntimeException;

final readonly class VideoExportController
{
    public function __construct(
        private PresignUploadHandler $presign,
        private EnqueueExportHandler $enqueue,
        private GetJobStatusHandler $status,
    ) {}

    public function index(): Response
    {
        return Inertia::render('video-export/Index');
    }

    public function presign(PresignUploadData $data, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $result = $this->presign->handle($data, $user->id);

        return response()->json(['data' => $result]);
    }

    public function store(EnqueueExportData $data, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $result = $this->enqueue->handle($data, $user->id);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $result], 202);
    }

    public function jobStatus(string $job_uuid, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $result = $this->status->handle($job_uuid, $user->id);

        return response()->json(['data' => $result]);
    }
}
