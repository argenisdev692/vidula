<?php

declare(strict_types=1);

namespace Modules\Products\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Clients\Infrastructure\Persistence\Eloquent\Models\ClientEloquentModel;
use Modules\Products\Application\Commands\BulkDeleteProductsHandler;
use Modules\Products\Application\Commands\BulkRestoreProductsHandler;
use Modules\Products\Application\Commands\CreateProductHandler;
use Modules\Products\Application\Commands\DeleteProductHandler;
use Modules\Products\Application\Commands\RestoreProductHandler;
use Modules\Products\Application\Commands\UpdateProductHandler;
use Modules\Products\Application\DTOs\ProductData;
use Modules\Products\Application\DTOs\ProductFilterData;
use Modules\Products\Application\Queries\GetProductHandler;
use Modules\Products\Application\Queries\GetProductShowHandler;
use Modules\Products\Application\Queries\ListProductsHandler;
use Shared\Application\DTOs\BulkUuidsData;

/**
 * Product catalog management. Authorization via `permission:*_PRODUCTS`
 * middleware. Thin: validate → handler → Inertia or JSON.
 */
final readonly class ProductController
{
    public function index(Request $request, ListProductsHandler $list): InertiaResponse|JsonResponse
    {
        $filters = ProductFilterData::validateAndCreate($request);
        $products = $list->handle($filters, min(max($request->integer('per_page', 15), 1), 100));

        return match ($request->expectsJson()) {
            true => response()->json($products),
            false => Inertia::render('products/Index', [
                'products' => $products,
                'filters' => $filters,
                // Lean ACTIVE clients for the create/edit dialog (explicit columns).
                'clients' => ClientEloquentModel::query()
                    ->where('status', 'ACTIVE')
                    ->orderBy('client_name')
                    ->select(['uuid', 'client_name'])
                    ->limit(200)
                    ->get(),
            ]),
        };
    }

    public function show(string $uuid, GetProductShowHandler $show): InertiaResponse|JsonResponse
    {
        $payload = $show->handle($uuid);

        return match (request()->expectsJson()) {
            true => response()->json(['data' => $payload]),
            false => Inertia::render('products/Show', $payload),
        };
    }

    public function store(Request $request, ProductData $data, CreateProductHandler $create): RedirectResponse
    {
        (void) $create->handle($data, (int) $request->user()->id);

        return back()->with('success', __('Product created.'));
    }

    public function update(string $uuid, ProductData $data, GetProductHandler $get, UpdateProductHandler $update): RedirectResponse
    {
        $update->handle($get->handle($uuid), $data);

        return back()->with('success', __('Product updated.'));
    }

    public function destroy(string $uuid, DeleteProductHandler $delete): RedirectResponse
    {
        $delete->handle($uuid);

        return back()->with('success', __('Product suspended.'));
    }

    public function restore(string $uuid, RestoreProductHandler $restore): RedirectResponse
    {
        $restore->handle($uuid);

        return back()->with('success', __('Product restored.'));
    }

    public function bulkDelete(BulkUuidsData $data, BulkDeleteProductsHandler $handler): RedirectResponse
    {
        $count = $handler->handle($data);

        return back()->with('success', __(':count products suspended.', ['count' => $count]));
    }

    public function bulkRestore(BulkUuidsData $data, BulkRestoreProductsHandler $handler): RedirectResponse
    {
        $count = $handler->handle($data);

        return back()->with('success', __(':count products restored.', ['count' => $count]));
    }
}
