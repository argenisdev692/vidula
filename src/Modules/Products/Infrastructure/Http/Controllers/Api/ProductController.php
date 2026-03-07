<?php

declare(strict_types=1);

namespace Modules\Products\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Products\Application\Commands\CreateProduct\CreateProductCommand;
use Modules\Products\Application\Commands\CreateProduct\CreateProductHandler;
use Modules\Products\Application\Commands\DeleteProduct\DeleteProductCommand;
use Modules\Products\Application\Commands\DeleteProduct\DeleteProductHandler;
use Modules\Products\Application\Commands\RestoreProduct\RestoreProductCommand;
use Modules\Products\Application\Commands\RestoreProduct\RestoreProductHandler;
use Modules\Products\Application\Commands\UpdateProduct\UpdateProductCommand;
use Modules\Products\Application\Commands\UpdateProduct\UpdateProductHandler;
use Modules\Products\Application\DTOs\CreateProductDTO;
use Modules\Products\Application\DTOs\ProductFilterDTO;
use Modules\Products\Application\DTOs\UpdateProductDTO;
use Modules\Products\Application\Queries\GetProduct\GetProductHandler;
use Modules\Products\Application\Queries\GetProduct\GetProductQuery;
use Modules\Products\Application\Queries\ListProduct\ListProductHandler;
use Modules\Products\Application\Queries\ListProduct\ListProductQuery;
use Modules\Products\Infrastructure\Http\Requests\CreateProductRequest;
use Modules\Products\Infrastructure\Http\Requests\UpdateProductRequest;

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

    /**
     * @OA\Get(
     *     path="/api/products/admin",
     *     summary="List products (paginated)",
     *     tags={"Products"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="page",   in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Paginated list",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ProductListItem")),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $filters = ProductFilterDTO::from($request->all());
        $result = $this->listHandler->handle(new ListProductQuery($filters));

        return response()->json($result);
    }

    /**
     * @OA\Get(
     *     path="/api/products/admin/{uuid}",
     *     summary="Get single product details",
     *     tags={"Products"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="Product found"),
     *     @OA\Response(response=404, description="Product not found")
     * )
     */
    public function show(string $uuid): JsonResponse
    {
        $result = $this->getHandler->handle(new GetProductQuery($uuid));

        return response()->json(['data' => $result]);
    }

    /**
     * @OA\Post(
     *     path="/api/products/admin",
     *     summary="Create product",
     *     tags={"Products"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/CreateProductDTO")),
     *     @OA\Response(response=201, description="Product created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(CreateProductRequest $request): JsonResponse
    {
        $dto = CreateProductDTO::from($request->validated());
        $this->createHandler->handle(new CreateProductCommand($dto));

        return response()->json([
            'message' => 'Product created successfully',
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/products/admin/{uuid}",
     *     summary="Update product details",
     *     tags={"Products"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/UpdateProductDTO")),
     *     @OA\Response(response=200, description="Product updated"),
     *     @OA\Response(response=404, description="Product not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(UpdateProductRequest $request, string $uuid): JsonResponse
    {
        $dto = UpdateProductDTO::from($request->validated());
        $this->updateHandler->handle(new UpdateProductCommand($uuid, $dto));

        return response()->json([
            'message' => 'Product updated successfully',
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/products/admin/{uuid}",
     *     summary="Soft delete product",
     *     tags={"Products"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="Product deleted"),
     *     @OA\Response(response=404, description="Product not found")
     * )
     */
    public function destroy(string $uuid): JsonResponse
    {
        $this->deleteHandler->handle(new DeleteProductCommand($uuid));

        return response()->json([
            'message' => 'Product deleted successfully',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/products/admin/bulk-delete",
     *     summary="Bulk soft delete products",
     *     tags={"Products"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="uuids", type="array", @OA\Items(type="string", format="uuid"))
     *     )),
     *     @OA\Response(response=204, description="Products deleted successfully")
     * )
     */
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

    /**
     * @OA\Patch(
     *     path="/api/products/admin/{uuid}/restore",
     *     summary="Restore soft deleted product",
     *     tags={"Products"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="Product restored successfully")
     * )
     */
    public function restore(string $uuid): JsonResponse
    {
        $this->restoreHandler->handle(new RestoreProductCommand($uuid));

        return response()->json([
            'message' => 'Product restored successfully',
        ]);
    }
}
