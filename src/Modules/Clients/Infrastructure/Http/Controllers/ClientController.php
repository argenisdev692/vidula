<?php

declare(strict_types=1);

namespace Modules\Clients\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Clients\Application\Commands\BulkDeleteClientsHandler;
use Modules\Clients\Application\Commands\BulkRestoreClientsHandler;
use Modules\Clients\Application\Commands\CreateClientHandler;
use Modules\Clients\Application\Commands\DeleteClientHandler;
use Modules\Clients\Application\Commands\RestoreClientHandler;
use Modules\Clients\Application\Commands\UpdateClientHandler;
use Modules\Clients\Application\DTOs\ClientData;
use Modules\Clients\Application\DTOs\ClientFilterData;
use Modules\Clients\Application\Queries\GetClientHandler;
use Modules\Clients\Application\Queries\ListClientsHandler;
use Shared\Application\DTOs\BulkUuidsData;

/**
 * CRM clients management. Authorization via `permission:*_CLIENTS` middleware.
 * Thin: validate → handler → Inertia or JSON.
 */
final readonly class ClientController
{
    public function index(Request $request, ListClientsHandler $list): InertiaResponse|JsonResponse
    {
        $filters = ClientFilterData::validateAndCreate($request);
        $clients = $list->handle($filters, min(max($request->integer('per_page', 15), 1), 100));

        return match ($request->expectsJson()) {
            true => response()->json($clients),
            false => Inertia::render('clients/Index', ['clients' => $clients, 'filters' => $filters]),
        };
    }

    public function show(Request $request, string $uuid, GetClientHandler $get): InertiaResponse|JsonResponse
    {
        $client = $get->handle($uuid);

        return match ($request->expectsJson()) {
            true => response()->json(['data' => $client]),
            false => Inertia::render('clients/Show', ['client' => $client]),
        };
    }

    public function store(Request $request, ClientData $data, CreateClientHandler $create): RedirectResponse
    {
        (void) $create->handle($data, (int) $request->user()->id);

        return back()->with('success', __('Client created.'));
    }

    public function update(string $uuid, ClientData $data, GetClientHandler $get, UpdateClientHandler $update): RedirectResponse
    {
        (void) $update->handle($get->handle($uuid), $data);

        return back()->with('success', __('Client updated.'));
    }

    public function destroy(string $uuid, DeleteClientHandler $delete): RedirectResponse
    {
        (void) $delete->handle($uuid);

        return back()->with('success', __('Client suspended.'));
    }

    public function restore(string $uuid, RestoreClientHandler $restore): RedirectResponse
    {
        (void) $restore->handle($uuid);

        return back()->with('success', __('Client restored.'));
    }

    public function bulkDelete(BulkUuidsData $data, BulkDeleteClientsHandler $handler): RedirectResponse
    {
        $count = $handler->handle($data);

        return back()->with('success', __(':count clients suspended.', ['count' => $count]));
    }

    public function bulkRestore(BulkUuidsData $data, BulkRestoreClientsHandler $handler): RedirectResponse
    {
        $count = $handler->handle($data);

        return back()->with('success', __(':count clients restored.', ['count' => $count]));
    }
}
