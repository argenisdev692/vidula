<?php

declare(strict_types=1);

namespace Modules\Products\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Products\Application\DTOs\ProductFilterData;
use Modules\Products\Application\Queries\GetProductHandler;
use Modules\Products\Application\Queries\ListProductsHandler;

/**
 * Sanctum-authenticated Products API (secondary). Primary UI remains
 * Inertia/web. Authorization via `permission:*_PRODUCTS` route middleware.
 * Scramble documents via return types + `auth:sanctum` — no manual annotations.
 */
final readonly class ProductApiController
{
    /**
     * List products.
     *
     * Returns a paginated, filterable catalog list. `per_page` is capped at 100
     * to bound resource consumption (OWASP API4).
     */
    public function index(Request $request, ListProductsHandler $list): JsonResponse
    {
        $filters = ProductFilterData::validateAndCreate($request);

        return response()->json($list->handle($filters, min(max($request->integer('per_page', 15), 1), 100)));
    }

    /**
     * Show a product.
     *
     * Returns a single catalog product by UUID, with its classroom or video
     * course detail.
     */
    public function show(string $uuid, GetProductHandler $get): JsonResponse
    {
        return response()->json(['data' => $get->handle($uuid)]);
    }
}
