<?php

declare(strict_types=1);

namespace Modules\Cvs\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Cvs\Application\DTOs\CvFilterData;
use Modules\Cvs\Application\Queries\GetCvHandler;
use Modules\Cvs\Application\Queries\ListCvsHandler;
use Shared\Domain\Ports\StoragePort;

/**
 * Sanctum-authenticated Cvs API (secondary). Primary UI remains Inertia/web.
 * Scramble documents via return types + `auth:sanctum` — no manual annotations.
 * Authorization is route middleware (`permission:*_CVS`).
 */
final readonly class CvApiController
{
    /**
     * List CVs.
     *
     * Returns a paginated, filterable CV list. `per_page` is capped at 100
     * to bound resource consumption (OWASP API4).
     */
    public function index(Request $request, ListCvsHandler $list): JsonResponse
    {
        $filters = CvFilterData::validateAndCreate($request);

        return response()->json($list->handle($filters, min(max($request->integer('per_page', 15), 1), 100)));
    }

    /**
     * Show a CV.
     *
     * Returns a single CV by UUID, including a short-lived signed download URL.
     */
    public function show(string $uuid, GetCvHandler $get, StoragePort $storage): JsonResponse
    {
        $cv = $get->handle($uuid);

        return response()->json([
            'data' => [
                ...$cv->toArray(),
                'download_url' => $storage->temporaryUrl($cv->file_path, now()->addMinutes(15)),
            ],
        ]);
    }
}
