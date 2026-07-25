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
 */
final readonly class CvApiController
{
    public function index(Request $request, ListCvsHandler $list): JsonResponse
    {
        abort_unless((bool) $request->user()?->hasPermissionTo('VIEW_ANY_CVS'), 403);

        $filters = CvFilterData::validateAndCreate($request);

        return response()->json($list->handle($filters, min(max($request->integer('per_page', 15), 1), 100)));
    }

    public function show(Request $request, string $uuid, GetCvHandler $get, StoragePort $storage): JsonResponse
    {
        abort_unless((bool) $request->user()?->hasPermissionTo('VIEW_CVS'), 403);

        $cv = $get->handle($uuid);

        return response()->json([
            'data' => [
                ...$cv->toArray(),
                'download_url' => $storage->temporaryUrl($cv->file_path, now()->addMinutes(15)),
            ],
        ]);
    }
}
