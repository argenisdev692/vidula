<?php

declare(strict_types=1);

namespace Modules\Cvs\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Cvs\Application\Commands\BulkDeleteCvsHandler;
use Modules\Cvs\Application\Commands\BulkRestoreCvsHandler;
use Modules\Cvs\Application\Commands\CreateCvHandler;
use Modules\Cvs\Application\Commands\DeleteCvHandler;
use Modules\Cvs\Application\Commands\RestoreCvHandler;
use Modules\Cvs\Application\Commands\UpdateCvHandler;
use Modules\Cvs\Application\DTOs\CvData;
use Modules\Cvs\Application\DTOs\CvFilterData;
use Modules\Cvs\Application\Queries\GetCvHandler;
use Modules\Cvs\Application\Queries\ListCvsHandler;
use Shared\Application\DTOs\BulkUuidsData;
use Shared\Domain\Ports\StoragePort;

/**
 * CV upload management. Authorization via `permission:*_CVS` middleware.
 * Thin: validate → handler → Inertia or JSON.
 */
final readonly class CvController
{
    public function index(Request $request, ListCvsHandler $list): InertiaResponse|JsonResponse
    {
        $filters = CvFilterData::validateAndCreate($request);
        $cvs = $list->handle($filters, min(max($request->integer('per_page', 15), 1), 100));

        return $request->expectsJson()
            ? response()->json($cvs)
            : Inertia::render('cvs/Index', ['cvs' => $cvs, 'filters' => $filters]);
    }

    public function show(string $uuid, GetCvHandler $get, StoragePort $storage): InertiaResponse|JsonResponse
    {
        $cv = $get->handle($uuid);
        $downloadUrl = $storage->temporaryUrl($cv->file_path, now()->addMinutes(15));

        $payload = [
            ...$cv->toArray(),
            'download_url' => $downloadUrl,
        ];

        return request()->expectsJson()
            ? response()->json(['data' => $payload])
            : Inertia::render('cvs/Show', ['cv' => $payload]);
    }

    public function store(Request $request, CvData $data, CreateCvHandler $create): RedirectResponse
    {
        (void) $create->handle($data, (int) $request->user()->id);

        return back()->with('success', __('CV uploaded.'));
    }

    public function update(string $uuid, CvData $data, GetCvHandler $get, UpdateCvHandler $update): RedirectResponse
    {
        $update->handle($get->handle($uuid), $data);

        return back()->with('success', __('CV updated.'));
    }

    public function destroy(string $uuid, DeleteCvHandler $delete): RedirectResponse
    {
        $delete->handle($uuid);

        return back()->with('success', __('CV suspended.'));
    }

    public function restore(string $uuid, RestoreCvHandler $restore): RedirectResponse
    {
        $restore->handle($uuid);

        return back()->with('success', __('CV restored.'));
    }

    public function bulkDelete(BulkUuidsData $data, BulkDeleteCvsHandler $handler): RedirectResponse
    {
        $count = $handler->handle($data);

        return back()->with('success', __(':count CVs suspended.', ['count' => $count]));
    }

    public function bulkRestore(BulkUuidsData $data, BulkRestoreCvsHandler $handler): RedirectResponse
    {
        $count = $handler->handle($data);

        return back()->with('success', __(':count CVs restored.', ['count' => $count]));
    }
}
