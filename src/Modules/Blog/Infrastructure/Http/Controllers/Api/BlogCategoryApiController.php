<?php

declare(strict_types=1);

namespace Modules\Blog\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Blog\Application\DTOs\BlogCategoryFilterData;
use Modules\Blog\Application\Queries\GetBlogCategoryHandler;
use Modules\Blog\Application\Queries\ListBlogCategoriesHandler;

/**
 * @group Blog Categories
 *
 * API endpoints for blog category lookup. Secondary documentation surface
 * for Sanctum-authenticated clients. The primary UI remains Inertia/web.
 */
final readonly class BlogCategoryApiController
{
    /**
     * List blog categories.
     *
     * Returns a paginated list of blog categories.
     *
     * @authenticated
     *
     * @queryParam per_page int Page size. Example: 15
     *
     * @response 403 {"message":"This action is unauthorized."}
     */
    public function index(Request $request, ListBlogCategoriesHandler $list): JsonResponse
    {
        abort_unless((bool) $request->user()?->hasPermissionTo('VIEW_ANY_BLOG_CATEGORIES'), 403);

        $filters = BlogCategoryFilterData::validateAndCreate($request);

        return response()->json($list->handle($filters, (int) $request->integer('per_page', 15)));
    }

    /**
     * Show a blog category.
     *
     * Returns a single blog category by UUID.
     *
     * @authenticated
     *
     * @urlParam uuid string required The blog category UUID. Example: 00000000-0000-0000-0000-000000000000
     *
     * @response 403 {"message":"This action is unauthorized."}
     * @response 404 {"message":"Not found."}
     */
    public function show(Request $request, string $uuid, GetBlogCategoryHandler $get): JsonResponse
    {
        abort_unless((bool) $request->user()?->hasPermissionTo('VIEW_BLOG_CATEGORIES'), 403);

        return response()->json(['data' => $get->handle($uuid)]);
    }
}
