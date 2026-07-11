<?php

declare(strict_types=1);

namespace Modules\Portfolio\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Portfolio\Application\DTOs\PortfolioFilterData;
use Modules\Portfolio\Application\Queries\GetPortfolioHandler;
use Modules\Portfolio\Application\Queries\ListPortfoliosHandler;

/**
 * API endpoints for authenticated portfolio lookup. Secondary Sanctum-
 * authenticated surface; the primary UI remains Inertia/web. Documented by
 * Scramble via return types + `auth:sanctum` detection — no manual annotations.
 */
final readonly class PortfolioApiController
{
    /**
     * List portfolios.
     *
     * Returns a paginated list of portfolio projects. `per_page` is capped at
     * 100 to bound resource consumption (OWASP API4).
     */
    public function index(Request $request, ListPortfoliosHandler $list): JsonResponse
    {
        abort_unless((bool) $request->user()?->hasPermissionTo('VIEW_ANY_PORTFOLIOS'), 403);

        $filters = PortfolioFilterData::validateAndCreate($request);

        return response()->json($list->handle($filters, min(max($request->integer('per_page', 15), 1), 100)));
    }

    /**
     * Show a portfolio.
     *
     * Returns a single portfolio project by UUID.
     */
    public function show(Request $request, string $uuid, GetPortfolioHandler $get): JsonResponse
    {
        abort_unless((bool) $request->user()?->hasPermissionTo('VIEW_PORTFOLIOS'), 403);

        return response()->json(['data' => $get->handle($uuid)]);
    }
}
