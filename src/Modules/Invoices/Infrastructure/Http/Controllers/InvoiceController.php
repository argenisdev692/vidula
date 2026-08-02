<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Invoices\Application\Commands\BulkDeleteInvoicesHandler;
use Modules\Invoices\Application\Commands\BulkRestoreInvoicesHandler;
use Modules\Invoices\Application\Commands\CreateInvoiceHandler;
use Modules\Invoices\Application\Commands\DeleteInvoiceHandler;
use Modules\Invoices\Application\Commands\RestoreInvoiceHandler;
use Modules\Invoices\Application\Commands\UpdateInvoiceHandler;
use Modules\Invoices\Application\DTOs\InvoiceData;
use Modules\Invoices\Application\DTOs\InvoiceFilterData;
use Modules\Invoices\Application\Queries\CheckInvoiceNumberHandler;
use Modules\Invoices\Application\Queries\GetInvoiceFormOptionsHandler;
use Modules\Invoices\Application\Queries\GetInvoiceHandler;
use Modules\Invoices\Application\Queries\ListInvoicesHandler;
use Modules\Invoices\Application\Queries\SuggestNextInvoiceNumberHandler;
use Shared\Application\DTOs\BulkUuidsData;

/**
 * Invoice CRUD. Authorization via `permission:*_INVOICES` middleware.
 * Thin: validate → handler → Inertia or JSON.
 */
final readonly class InvoiceController
{
    public function index(
        Request $request,
        ListInvoicesHandler $list,
        SuggestNextInvoiceNumberHandler $suggest,
        GetInvoiceFormOptionsHandler $formOptions,
    ): InertiaResponse|JsonResponse {
        $filters = InvoiceFilterData::validateAndCreate($request);
        $invoices = $list->handle($filters, min(max($request->integer('per_page', 15), 1), 100));

        return match ($request->expectsJson()) {
            true => response()->json($invoices),
            false => Inertia::render('invoices/Index', [
                'invoices' => $invoices,
                'filters' => $filters,
                'nextInvoiceNumber' => $suggest->handle(),
                ...$formOptions->handle(),
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

    public function checkNumber(Request $request, CheckInvoiceNumberHandler $check): JsonResponse
    {
        $validated = $request->validate([
            'invoice_number' => ['required', 'string', 'max:32', 'regex:/^(\d{1,6}|\d{1,6}\/\d{4})$/'],
            'ignore' => ['nullable', 'uuid'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ]);

        $year = isset($validated['year']) ? (int) $validated['year'] : null;

        return response()->json($check->handle(
            $validated['invoice_number'],
            $validated['ignore'] ?? null,
            $year,
        ));
    }

    public function store(Request $request, InvoiceData $data, CreateInvoiceHandler $create): RedirectResponse
    {
        (void) $create->handle($data, (int) $request->user()->id);

        return back()->with('success', __('Invoice created.'));
    }

    public function update(string $uuid, InvoiceData $data, GetInvoiceHandler $get, UpdateInvoiceHandler $update): RedirectResponse
    {
        (void) $update->handle($get->handle($uuid), $data);

        return back()->with('success', __('Invoice updated.'));
    }

    public function destroy(string $uuid, DeleteInvoiceHandler $delete): RedirectResponse
    {
        (void) $delete->handle($uuid);

        return back()->with('success', __('Invoice deleted.'));
    }

    public function restore(string $uuid, RestoreInvoiceHandler $restore): RedirectResponse
    {
        (void) $restore->handle($uuid);

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
