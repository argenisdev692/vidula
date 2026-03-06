<?php

declare(strict_types=1);

namespace Modules\Blog\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Blog\Application\Commands\CreatePost\CreatePostCommand;
use Modules\Blog\Application\Commands\CreatePost\CreatePostHandler;
use Modules\Blog\Application\Commands\DeletePost\DeletePostCommand;
use Modules\Blog\Application\Commands\DeletePost\DeletePostHandler;
use Modules\Blog\Application\Commands\RestorePost\RestorePostCommand;
use Modules\Blog\Application\Commands\RestorePost\RestorePostHandler;
use Modules\Blog\Application\Commands\UpdatePost\UpdatePostCommand;
use Modules\Blog\Application\Commands\UpdatePost\UpdatePostHandler;
use Modules\Blog\Application\DTOs\CreatePostDTO;
use Modules\Blog\Application\DTOs\PostFilterDTO;
use Modules\Blog\Application\DTOs\UpdatePostDTO;
use Modules\Blog\Application\Queries\GetPost\GetPostHandler;
use Modules\Blog\Application\Queries\GetPost\GetPostQuery;
use Modules\Blog\Application\Queries\ListPosts\ListPostsHandler;
use Modules\Blog\Application\Queries\ListPosts\ListPostsQuery;
use Modules\Blog\Infrastructure\Http\Requests\CreatePostRequest;
use Modules\Blog\Infrastructure\Http\Requests\PostFilterRequest;
use Modules\Blog\Infrastructure\Http\Requests\UpdatePostRequest;
use Modules\Blog\Infrastructure\Http\Resources\PostResource;

final class AdminPostController
{
    public function __construct(
        private readonly CreatePostHandler $createHandler,
        private readonly UpdatePostHandler $updateHandler,
        private readonly DeletePostHandler $deleteHandler,
        private readonly RestorePostHandler $restoreHandler,
        private readonly ListPostsHandler $listHandler,
        private readonly GetPostHandler $getHandler,
    ) {
    }

    public function index(PostFilterRequest $request): JsonResponse
    {
        $filters = PostFilterDTO::from($request->validated());
        $result = $this->listHandler->handle(new ListPostsQuery($filters));

        return response()->json($result);
    }

    public function show(string $uuid): JsonResponse
    {
        $post = $this->getHandler->handle(new GetPostQuery($uuid));

        return response()->json([
            'data' => new PostResource($post),
        ]);
    }

    public function store(CreatePostRequest $request): JsonResponse
    {
        $post = $this->createHandler->handle(new CreatePostCommand(CreatePostDTO::from($request->validated())));

        return response()->json([
            'data' => new PostResource($post),
        ], 201);
    }

    public function update(UpdatePostRequest $request, string $uuid): JsonResponse
    {
        $post = $this->updateHandler->handle(new UpdatePostCommand($uuid, UpdatePostDTO::from($request->validated())));

        return response()->json([
            'data' => new PostResource($post),
        ]);
    }

    public function destroy(string $uuid): JsonResponse
    {
        $this->deleteHandler->handle(new DeletePostCommand($uuid));

        return response()->json(null, 204);
    }

    public function restore(string $uuid): JsonResponse
    {
        $this->restoreHandler->handle(new RestorePostCommand($uuid));

        return response()->json(['message' => 'Post restored successfully.']);
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'uuids' => ['required', 'array'],
            'uuids.*' => ['required', 'string', 'uuid'],
        ]);

        foreach ($validated['uuids'] as $uuid) {
            $this->deleteHandler->handle(new DeletePostCommand($uuid));
        }

        return response()->json(null, 204);
    }
}
