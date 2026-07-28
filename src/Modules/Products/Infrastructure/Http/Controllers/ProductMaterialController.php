<?php

declare(strict_types=1);

namespace Modules\Products\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Products\Application\Commands\ReplaceMaterialHandler;
use Modules\Products\Application\DTOs\ReplaceMaterialData;
use Modules\Products\Application\Queries\GetProductHandler;
use Modules\Products\Application\Queries\ListProductMaterialsHandler;
use Modules\Products\Domain\Ports\ProductMaterialRepositoryPort;
use Shared\Domain\Ports\StoragePort;

/**
 * Course materials: list, time-limited download, and Markdown/PDF replace
 * (spec US-4).
 *
 * Downloads never expose a permanent URL — the private object is handed back as
 * a short-lived signed link (OWASP / BACKEND-PHP §5).
 */
final readonly class ProductMaterialController
{
    private const int DOWNLOAD_LINK_TTL_MINUTES = 5;

    public function __construct(
        private ProductMaterialRepositoryPort $materials,
        private StoragePort $storage,
    ) {}

    public function index(
        Request $request,
        string $uuid,
        GetProductHandler $get,
        ListProductMaterialsHandler $list,
    ): JsonResponse {
        return response()->json(['data' => $list->handle($get->handle($uuid))]);
    }

    public function download(string $uuid, string $materialUuid, GetProductHandler $get): JsonResponse
    {
        $product = $get->handle($uuid);

        $material = $this->materials->findByUuidForProduct($materialUuid, $product->id);
        abort_if($material === null, 404);
        abort_if($material->path === null, 404);

        return response()->json([
            'data' => [
                'url' => $this->storage->temporaryUrl(
                    $material->path,
                    now()->addMinutes(self::DOWNLOAD_LINK_TTL_MINUTES),
                ),
                'expires_in' => self::DOWNLOAD_LINK_TTL_MINUTES * 60,
            ],
        ]);
    }

    public function replace(
        Request $request,
        string $uuid,
        string $materialUuid,
        ReplaceMaterialData $data,
        GetProductHandler $get,
        ReplaceMaterialHandler $replace,
    ): RedirectResponse|JsonResponse {
        $product = $get->handle($uuid);

        $material = $this->materials->findByUuidForProduct($materialUuid, $product->id);
        abort_if($material === null, 404);

        $updated = $replace->handle($material, $data, $request->user());

        return match ($request->expectsJson()) {
            true => response()->json(['data' => $updated]),
            false => back()->with('success', __('Material replaced.')),
        };
    }
}
