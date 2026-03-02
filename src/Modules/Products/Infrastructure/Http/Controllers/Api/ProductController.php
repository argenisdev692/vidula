<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Product\Application\Commands\CreateProduct\CreateProductCommand;
use Modules\Product\Application\Commands\CreateProduct\CreateProductHandler;
use Modules\Product\Application\Commands\DeleteProduct\DeleteProductCommand;
use Modules\Product\Application\Commands\DeleteProduct\DeleteProductHandler;
use Modules\Product\Application\Commands\RestoreProduct\RestoreProductCommand;
use Modules\Product\Application\Commands\RestoreProduct\RestoreProductHandler;
use Modules\Product\Application\Commands\UpdateProduct\UpdateProductCommand;
use Modules\Product\Application\Commands\UpdateProduct\UpdateProductHandler;
use Modules\Product\Application\DTOs\ProductFilterDTO;
use Modules\Product\Application\DTOs\CreateProductDTO;
use Modules\Product\Application\DTOs\UpdateProductDTO;
use Modules\Product\Application\Queries\GetProduct\GetProductHandler;
use Modules\Product\Application\Queries\GetProduct\GetProductQuery;
use Modules\Product\Application\Queries\ListProduct\ListProductHandler;
use Modules\Product\Application\Queries\ListProduct\ListProductQuery;
use Modules\Product\Infrastructure\Http\Requests\CreateProductRequest;
use Modules\Product\Infrastructure\Http\Requests\UpdateProductRequest;
use Modules\Product\Infrastructure\Persistence\Export\ProductExcelExport;
use Modules\Product\Infrastructure\Persistence\Export\ProductPdfExport;
use Maatwebsite\Excel\Facades\Excel;
/**
 * ProductController
 */
final class ProductController
{
    public function __construct(
        private readonly CreateProductHandler $createHandler,
        private readonly UpdateProductHandler $updateHandler,
        private readonly DeleteProductHandler $deleteHandler,
        private readonly RestoreProductHandler $restoreHandler,
        private readonly ListProductHandler $listHandler,
        private readonly GetProductHandler $getHandler
    ) {
    }

    public function export(Request $request): mixed
    {
        $format = $request->query('format', 'excel');
        $filters = ProductFilterDTO::from($request->all());
        $query = new ListProductQuery($filters);

        if ($format === 'pdf') {
            $pdfExport = new ProductPdfExport($this->listHandler, $query);
            return $pdfExport->export();
        }

        $excelExport = new ProductExcelExport($this->listHandler, $query);
        return Excel::download($excelExport, 'products.xlsx');
    }

    public function index(Request $request): JsonResponse
    {
        $filters = ProductFilterDTO::from($request->all());
        $result = $this->listHandler->handle(new ListProductQuery($filters));

        return response()->json($result);
    }

    public function show(Request $request, ?string $uuid = null): JsonResponse
    {
        $targetUuid = $uuid ?? $request->user()?->uuid;

        if (!$targetUuid) {
            return response()->json(['message' => 'User context not found'], 401);
        }

        $result = $this->getHandler->handle(new GetProductQuery($targetUuid));

        return response()->json(['data' => $result]);
    }

    public function store(CreateProductRequest $request): JsonResponse
    {
        $dto = CreateProductDTO::from($request->validated());
        $this->createHandler->handle(new CreateProductCommand($dto));

        return response()->json([
            'message' => 'Product created successfully',
        ], 201);
    }

    public function update(UpdateProductRequest $request, ?string $uuid = null): JsonResponse
    {
        $targetUuid = $uuid ?? $request->user()?->uuid;

        if (!$targetUuid) {
            return response()->json(['message' => 'User context not found'], 401);
        }

        $dto = UpdateProductDTO::from($request->validated());
        $this->updateHandler->handle(new UpdateProductCommand($targetUuid, $dto));

        return response()->json([
            'message' => 'Product updated successfully',
        ]);
    }

    public function destroy(string $uuid): JsonResponse
    {
        $this->deleteHandler->handle(new DeleteProductCommand($uuid));

        return response()->json([
            'message' => 'Product deleted successfully',
        ]);
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'uuids' => ['required', 'array'],
            'uuids.*' => ['required', 'string', 'uuid'],
        ]);

        foreach ($validated['uuids'] as $uuid) {
            $this->deleteHandler->handle(new DeleteProductCommand($uuid));
        }

        return response()->json(null, 204);
    }

    public function restore(string $uuid): JsonResponse
    {
        $this->restoreHandler->handle(new RestoreProductCommand($uuid));

        return response()->json([
            'message' => 'Product restored successfully',
        ]);
    }
}
