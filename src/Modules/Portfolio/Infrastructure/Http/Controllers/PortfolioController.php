<?php

declare(strict_types=1);

namespace Modules\Portfolio\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Portfolio\Application\Commands\BulkDeletePortfoliosHandler;
use Modules\Portfolio\Application\Commands\BulkRestorePortfoliosHandler;
use Modules\Portfolio\Application\Commands\CreatePortfolioHandler;
use Modules\Portfolio\Application\Commands\DeletePortfolioHandler;
use Modules\Portfolio\Application\Commands\RestorePortfolioHandler;
use Modules\Portfolio\Application\Commands\UpdatePortfolioHandler;
use Modules\Portfolio\Application\DTOs\PortfolioData;
use Modules\Portfolio\Application\DTOs\PortfolioFilterData;
use Modules\Portfolio\Application\Queries\GetPortfolioHandler;
use Modules\Portfolio\Application\Queries\ListPortfoliosHandler;
use Shared\Application\DTOs\BulkUuidsData;

/**
 * Portfolio project management. Every route is authorized via
 * `permission:*_PORTFOLIOS` middleware (not roles) — see Routes. Controller
 * stays thin: validate → handler → response, branching Inertia vs JSON.
 */
final readonly class PortfolioController
{
    public function index(Request $request, ListPortfoliosHandler $list): InertiaResponse|JsonResponse
    {
        $filters = PortfolioFilterData::validateAndCreate($request);
        $portfolios = $list->handle($filters, min(max($request->integer('per_page', 15), 1), 100));

        return $request->expectsJson()
            ? response()->json($portfolios)
            : Inertia::render('portfolios/Index', ['portfolios' => $portfolios, 'filters' => $filters]);
    }

    public function show(string $uuid, GetPortfolioHandler $get): InertiaResponse|JsonResponse
    {
        $portfolio = $get->handle($uuid);

        return request()->expectsJson()
            ? response()->json(['data' => $portfolio])
            : Inertia::render('portfolios/Show', ['portfolio' => $portfolio]);
    }

    public function store(Request $request, PortfolioData $data, CreatePortfolioHandler $create): RedirectResponse
    {
        (void) $create->handle($data, (int) $request->user()->id);

        return back()->with('success', __('Portfolio project created.'));
    }

    public function update(string $uuid, PortfolioData $data, GetPortfolioHandler $get, UpdatePortfolioHandler $update): RedirectResponse
    {
        (void) $update->handle($get->handle($uuid), $data);

        return back()->with('success', __('Portfolio project updated.'));
    }

    public function destroy(string $uuid, DeletePortfolioHandler $delete): RedirectResponse
    {
        (void) $delete->handle($uuid);

        return back()->with('success', __('Portfolio project suspended.'));
    }

    public function restore(string $uuid, RestorePortfolioHandler $restore): RedirectResponse
    {
        (void) $restore->handle($uuid);

        return back()->with('success', __('Portfolio project restored.'));
    }

    public function bulkDelete(BulkUuidsData $data, BulkDeletePortfoliosHandler $handler): RedirectResponse
    {
        $count = $handler->handle($data);

        return back()->with('success', __(':count portfolio projects suspended.', ['count' => $count]));
    }

    public function bulkRestore(BulkUuidsData $data, BulkRestorePortfoliosHandler $handler): RedirectResponse
    {
        $count = $handler->handle($data);

        return back()->with('success', __(':count portfolio projects restored.', ['count' => $count]));
    }
}
