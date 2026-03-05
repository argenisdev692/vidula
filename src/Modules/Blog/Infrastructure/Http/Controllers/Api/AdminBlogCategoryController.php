<?php

declare(strict_types=1);

namespace Modules\Blog\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Blog\Application\Commands\CreateBlogCategory\CreateBlogCategoryCommand;
use Modules\Blog\Application\Commands\CreateBlogCategory\CreateBlogCategoryHandler;
use Modules\Blog\Application\Commands\DeleteBlogCategory\DeleteBlogCategoryCommand;
use Modules\Blog\Application\Commands\DeleteBlogCategory\DeleteBlogCategoryHandler;
use Modules\Blog\Application\Commands\UpdateBlogCategory\UpdateBlogCategoryCommand;
use Modules\Blog\Application\Commands\UpdateBlogCategory\UpdateBlogCategoryHandler;
use Modules\Blog\Application\Commands\RestoreBlogCategory\RestoreBlogCategoryCommand;
use Modules\Blog\Application\Commands\RestoreBlogCategory\RestoreBlogCategoryHandler;
use Modules\Blog\Application\DTOs\BlogCategoryFilterDTO;
use Modules\Blog\Application\DTOs\CreateBlogCategoryDTO;
use Modules\Blog\Application\DTOs\UpdateBlogCategoryDTO;
use Modules\Blog\Application\Queries\GetBlogCategory\GetBlogCategoryHandler;
use Modules\Blog\Application\Queries\GetBlogCategory\GetBlogCategoryQuery;
use Modules\Blog\Application\Queries\ListBlogCategories\ListBlogCategoriesHandler;
use Modules\Blog\Application\Queries\ListBlogCategories\ListBlogCategoriesQuery;
use Modules\Blog\Infrastructure\Http\Requests\BlogCategoryFilterRequest;
use Modules\Blog\Infrastructure\Http\Requests\CreateBlogCategoryRequest;
use Modules\Blog\Infrastructure\Http\Requests\UpdateBlogCategoryRequest;
use Modules\Blog\Infrastructure\Http\Resources\BlogCategoryResource;

/**
 * AdminBlogCategoryController — Full CRUD Web-JSON API for blog category management.
 */
final class AdminBlogCategoryController
{
    public function __construct(
        private readonly CreateBlogCategoryHandler $createHandler,
        private readonly UpdateBlogCategoryHandler $updateHandler,
        private readonly DeleteBlogCategoryHandler $deleteHandler,
        private readonly RestoreBlogCategoryHandler $restoreHandler,
        private readonly ListBlogCategoriesHandler $listHandler,
        private readonly GetBlogCategoryHandler $getHandler,
    ) {
    }

    public function index(BlogCategoryFilterRequest $request): JsonResponse
    {
        $filters = BlogCategoryFilterDTO::from($request->validated());
        $result = $this->listHandler->handle(new ListBlogCategoriesQuery($filters));

        return response()->json($result);
    }

    public function show(string $uuid): JsonResponse
    {
        $category = $this->getHandler->handle(new GetBlogCategoryQuery($uuid));

        return response()->json([
            'data' => new BlogCategoryResource($category),
        ]);
    }

    public function store(CreateBlogCategoryRequest $request): JsonResponse
    {
        $dto = CreateBlogCategoryDTO::from($request->validated());
        $category = $this->createHandler->handle(new CreateBlogCategoryCommand($dto));

        return response()->json([
            'data' => new BlogCategoryResource($category),
        ], 201);
    }

    public function update(UpdateBlogCategoryRequest $request, string $uuid): JsonResponse
    {
        $dto = UpdateBlogCategoryDTO::from($request->validated());
        $category = $this->updateHandler->handle(new UpdateBlogCategoryCommand($uuid, $dto));

        return response()->json([
            'data' => new BlogCategoryResource($category),
        ]);
    }

    public function destroy(string $uuid): JsonResponse
    {
        $this->deleteHandler->handle(new DeleteBlogCategoryCommand($uuid));

        return response()->json(null, 204);
    }

    public function restore(string $uuid): JsonResponse
    {
        $this->restoreHandler->handle(new RestoreBlogCategoryCommand($uuid));

        return response()->json(['message' => 'Blog category restored successfully.']);
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'uuids' => ['required', 'array'],
            'uuids.*' => ['required', 'string', 'uuid'],
        ]);

        foreach ($validated['uuids'] as $uuid) {
            $this->deleteHandler->handle(new DeleteBlogCategoryCommand($uuid));
        }

        return response()->json(null, 204);
    }
}
