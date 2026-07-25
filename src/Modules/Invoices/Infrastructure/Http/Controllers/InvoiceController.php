<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Http\Controllers;

use App\Models\CompanyData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Clients\Infrastructure\Persistence\Eloquent\Models\ClientEloquentModel;
use Modules\Invoices\Application\Commands\BulkDeleteInvoicesHandler;
use Modules\Invoices\Application\Commands\BulkRestoreInvoicesHandler;
use Modules\Invoices\Application\Commands\CreateInvoiceHandler;
use Modules\Invoices\Application\Commands\DeleteInvoiceHandler;
use Modules\Invoices\Application\Commands\RestoreInvoiceHandler;
use Modules\Invoices\Application\Commands\UpdateInvoiceHandler;
use Modules\Invoices\Application\DTOs\InvoiceData;
use Modules\Invoices\Application\DTOs\InvoiceFilterData;
use Modules\Invoices\Application\Queries\GetInvoiceHandler;
use Modules\Invoices\Application\Queries\ListInvoicesHandler;
use Modules\Invoices\Application\Queries\SuggestNextInvoiceNumberHandler;
use Modules\Services\Domain\Ports\ServiceRepositoryPort;
use Shared\Application\DTOs\BulkUuidsData;

/**
 * Invoice CRUD. Authorization via `permission:*_INVOICES` middleware.
 */
final readonly class InvoiceController
{
    public function index(
        Request $request,
        ListInvoicesHandler $list,
        SuggestNextInvoiceNumberHandler $suggest,
        ServiceRepositoryPort $services,
    ): InertiaResponse|JsonResponse {
        $filters = InvoiceFilterData::validateAndCreate($request);
        $invoices = $list->handle($filters, min(max($request->integer('per_page', 15), 1), 100));

        return match ($request->expectsJson()) {
            true => response()->json($invoices),
            false => Inertia::render('invoices/Index', [
                'invoices' => $invoices,
                'filters' => $filters,
                'nextInvoiceNumber' => $suggest->handle(),
                'clients' => ClientEloquentModel::query()
                    ->where('status', 'ACTIVE')
                    ->orderBy('client_name')
                    ->select(['uuid', 'client_name', 'tax_id', 'nif', 'address', 'email'])
                    ->limit(200)
                    ->get(),
                'services' => $services->listActive()->map(static fn ($s) => [
                    'uuid' => $s->uuid,
                    'name' => $s->name,
                    'description' => $s->description,
                ])->values(),
                'defaultNotes' => CompanyData::query()->orderBy('id')->value('invoice_notes'),
            ]),
        };
    }

    public function show(string $uuid, GetInvoiceHandler $get): InertiaResponse|JsonResponse
    {
        $invoice = $get->handle($uuid);

        return match (request()->expectsJson()) {
            true => response()->json(['data' => $invoice]),
            false => Inertia::render('invoices/Show', ['invoice' => $invoice]),
        };
    }

    public function nextNumber(Request $request, SuggestNextInvoiceNumberHandler $suggest): JsonResponse
    {
        $year = $request->integer('year') ?: null;

        return response()->json($suggest->handle($year > 0 ? $year : null));
    }

    public function store(Request $request, InvoiceData $data, CreateInvoiceHandler $create): RedirectResponse
    {
        (void) $create->handle($data, (int) $request->user()->id);

        return back()->with('success', __('Invoice created.'));
    }

    public function update(string $uuid, InvoiceData $data, GetInvoiceHandler $get, UpdateInvoiceHandler $update): RedirectResponse
    {
        $update->handle($get->handle($uuid), $data);

        return back()->with('success', __('Invoice updated.'));
    }

    public function destroy(string $uuid, DeleteInvoiceHandler $delete): RedirectResponse
    {
        $delete->handle($uuid);

        return back()->with('success', __('Invoice deleted.'));
    }

    public function restore(string $uuid, RestoreInvoiceHandler $restore): RedirectResponse
    {
        $restore->handle($uuid);

        return back()->with('success', __('Invoice restored.'));
    }

    public function bulkDelete(BulkUuidsData $data, BulkDeleteInvoicesHandler $handler): RedirectResponse
    {
        $count = $handler->handle($data);

        return back()->with('success', __(':count invoices deleted.', ['count' => $count]));
    }

    public function bulkRestore(BulkUuidsData $data, BulkRestoreInvoicesHandler $handler): RedirectResponse
    {
        $count = $handler->handle($data);

        return back()->with('success', __(':count invoices restored.', ['count' => $count]));
    }
}
