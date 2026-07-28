<?php

declare(strict_types=1);

namespace Modules\Services\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Services\Application\Commands\BulkDeleteServicesHandler;
use Modules\Services\Application\Commands\BulkRestoreServicesHandler;
use Modules\Services\Application\Commands\CreateServiceHandler;
use Modules\Services\Application\Commands\DeleteServiceHandler;
use Modules\Services\Application\Commands\RestoreServiceHandler;
use Modules\Services\Application\Commands\UpdateServiceHandler;
use Modules\Services\Application\DTOs\ServiceData;
use Modules\Services\Application\DTOs\ServiceFilterData;
use Modules\Services\Application\Queries\GetServiceHandler;
use Modules\Services\Application\Queries\ListServicesHandler;
use Shared\Application\DTOs\BulkUuidsData;

/**
 * Service catalog management. Every route is authorized via
 * `permission:*_SERVICES` middleware (not roles) — see Routes. Controller
 * stays thin: validate → handler → response, branching Inertia vs JSON.
 */
final readonly class ServiceController
{
    public function index(Request $request, ListServicesHandler $list): InertiaResponse|JsonResponse
    {
        $filters = ServiceFilterData::validateAndCreate($request);
        $services = $list->handle($filters, min(max($request->integer('per_page', 15), 1), 100));

        return match ($request->expectsJson()) {
            true => response()->json($services),
            false => Inertia::render('services/Index', ['services' => $services, 'filters' => $filters]),
        };
    }

    public function show(Request $request, string $uuid, GetServiceHandler $get): InertiaResponse|JsonResponse
    {
        $service = $get->handle($uuid);

        return match ($request->expectsJson()) {
            true => response()->json(['data' => $service]),
            false => Inertia::render('services/Show', ['service' => $service]),
        };
    }

    public function store(Request $request, ServiceData $data, CreateServiceHandler $create): RedirectResponse
    {
        (void) $create->handle($data, (int) $request->user()->id);

        return back()->with('success', __('Service created.'));
    }

    public function update(string $uuid, ServiceData $data, GetServiceHandler $get, UpdateServiceHandler $update): RedirectResponse
    {
        (void) $update->handle($get->handle($uuid), $data);

        return back()->with('success', __('Service updated.'));
    }

    public function destroy(string $uuid, DeleteServiceHandler $delete): RedirectResponse
    {
        (void) $delete->handle($uuid);

        return back()->with('success', __('Service suspended.'));
    }

    public function restore(string $uuid, RestoreServiceHandler $restore): RedirectResponse
    {
        (void) $restore->handle($uuid);

        return back()->with('success', __('Service restored.'));
    }

    public function bulkDelete(BulkUuidsData $data, BulkDeleteServicesHandler $handler): RedirectResponse
    {
        $count = $handler->handle($data);

        return back()->with('success', __(':count services suspended.', ['count' => $count]));
    }

    public function bulkRestore(BulkUuidsData $data, BulkRestoreServicesHandler $handler): RedirectResponse
    {
        $count = $handler->handle($data);

        return back()->with('success', __(':count services restored.', ['count' => $count]));
    }
}
