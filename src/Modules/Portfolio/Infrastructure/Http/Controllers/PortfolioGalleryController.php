<?php

declare(strict_types=1);

namespace Modules\Portfolio\Infrastructure\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Modules\Portfolio\Application\Commands\AddPortfolioGalleryImageHandler;
use Modules\Portfolio\Application\Commands\DeletePortfolioGalleryImageHandler;
use Modules\Portfolio\Application\Commands\ReorderPortfolioGalleryHandler;
use Modules\Portfolio\Application\DTOs\AddPortfolioGalleryImageData;
use Modules\Portfolio\Application\Queries\GetPortfolioHandler;
use Shared\Application\DTOs\BulkUuidsData;

/**
 * Gallery image management scoped under a single portfolio. Gated by the same
 * `permission:UPDATE_PORTFOLIOS` as the parent aggregate — gallery photos are
 * part of "updating" a portfolio's content, not a separate concern (see
 * Routes). Controller stays thin: resolve the parent, validate, invoke handler.
 */
final readonly class PortfolioGalleryController
{
    public function store(string $uuid, AddPortfolioGalleryImageData $data, GetPortfolioHandler $get, AddPortfolioGalleryImageHandler $add): RedirectResponse
    {
        $add->handle($get->handle($uuid), $data);

        return back()->with('success', __('Gallery image added.'));
    }

    public function destroy(string $uuid, string $mediaUuid, GetPortfolioHandler $get, DeletePortfolioGalleryImageHandler $delete): RedirectResponse
    {
        $delete->handle($get->handle($uuid), $mediaUuid);

        return back()->with('success', __('Gallery image removed.'));
    }

    public function reorder(string $uuid, BulkUuidsData $data, GetPortfolioHandler $get, ReorderPortfolioGalleryHandler $reorder): RedirectResponse
    {
        $reorder->handle($get->handle($uuid), $data);

        return back()->with('success', __('Gallery order updated.'));
    }
}
