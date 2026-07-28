<?php

declare(strict_types=1);

namespace Modules\Products\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Products\Application\Commands\RequestProductPackageHandler;
use Modules\Products\Application\Queries\GetProductHandler;
use Modules\Products\Domain\Ports\ContentGenerationRepositoryPort;
use Shared\Domain\Ports\StoragePort;

/**
 * ZIP deliverable for a completed generation (spec US-5). Packaging is queued,
 * and the finished archive is served through a short-lived signed URL rather
 * than a public path.
 */
final readonly class ProductPackageController
{
    private const int DOWNLOAD_LINK_TTL_MINUTES = 10;

    public function __construct(
        private ContentGenerationRepositoryPort $generations,
        private StoragePort $storage,
    ) {}

    public function store(
        Request $request,
        string $uuid,
        GetProductHandler $get,
        RequestProductPackageHandler $requestPackage,
    ): RedirectResponse|JsonResponse {
        $generation = $requestPackage->handle($get->handle($uuid), $request->user());

        return match ($request->expectsJson()) {
            true => response()->json(['data' => ['generation_uuid' => $generation->uuid]], 202),
            false => back()->with('success', __('Package build queued.')),
        };
    }

    public function download(string $uuid, GetProductHandler $get): JsonResponse
    {
        $product = $get->handle($uuid);

        $generation = $this->generations->latestCompletedFor($product->id);
        abort_if($generation === null || $generation->zip_path === null, 404);

        return response()->json([
            'data' => [
                'url' => $this->storage->temporaryUrl(
                    $generation->zip_path,
                    now()->addMinutes(self::DOWNLOAD_LINK_TTL_MINUTES),
                ),
                'expires_in' => self::DOWNLOAD_LINK_TTL_MINUTES * 60,
            ],
        ]);
    }
}
