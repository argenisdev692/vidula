<?php

declare(strict_types=1);

namespace Modules\Post\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Post\Application\DTOs\PostFilterData;
use Modules\Post\Application\Queries\GetPostHandler;
use Modules\Post\Application\Queries\ListPostsHandler;

/**
 * API endpoints for post lookup. Secondary Sanctum-authenticated surface; the
 * primary UI remains Inertia/web. Documented by Scramble via return types +
 * `auth:sanctum` detection — no manual annotations.
 */
final readonly class PostApiController
{
    /**
     * List posts.
     *
     * Returns a paginated list of posts. `per_page` is capped at 100 to bound
     * resource consumption (OWASP API4).
     */
    public function index(Request $request, ListPostsHandler $list): JsonResponse
    {
        abort_unless((bool) $request->user()?->hasPermissionTo('VIEW_ANY_POSTS'), 403);

        $filters = PostFilterData::validateAndCreate($request);

        return response()->json($list->handle($filters, min(max($request->integer('per_page', 15), 1), 100)));
    }

    /**
     * Show a post.
     *
     * Returns a single post by UUID.
     */
    public function show(Request $request, string $uuid, GetPostHandler $get): JsonResponse
    {
        abort_unless((bool) $request->user()?->hasPermissionTo('VIEW_POSTS'), 403);

        return response()->json(['data' => $get->handle($uuid)]);
    }
}
