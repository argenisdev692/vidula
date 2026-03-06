<?php

declare(strict_types=1);

namespace Modules\Blog\Application\Queries\ListPosts;

use Illuminate\Support\Facades\Cache;
use Modules\Blog\Application\DTOs\PostFilterDTO;
use Modules\Blog\Application\Queries\ReadModels\PostListReadModel;
use Modules\Blog\Domain\Ports\PostRepositoryPort;

final readonly class ListPostsHandler
{
    public function __construct(
        private PostRepositoryPort $repository,
    ) {
    }

    public function handle(ListPostsQuery $query): array
    {
        $filters = $query->filters;
        $cacheKey = 'posts_list_' . md5(serialize($filters->toArray()));
        $ttl = 60 * 15;

        try {
            return Cache::tags(['posts_list'])->remember($cacheKey, $ttl, fn() => $this->fetchAndMap($filters));
        } catch (\Exception) {
            return Cache::remember($cacheKey, $ttl, fn() => $this->fetchAndMap($filters));
        }
    }

    private function fetchAndMap(PostFilterDTO $filters): array
    {
        $result = $this->repository->findAllPaginated(
            filters: $filters->toArray(),
            page: $filters->page,
            perPage: $filters->perPage,
        );

        return [
            'data' => $result['data']
                |> (fn($posts) => array_map(
                    fn($post) => new PostListReadModel(
                        uuid: $post->uuid,
                        postTitle: $post->title,
                        postTitleSlug: $post->slug,
                        postExcerpt: $post->excerpt,
                        postCoverImage: $post->coverImage,
                        categoryName: $post->categoryName,
                        postStatus: $post->status,
                        publishedAt: $post->publishedAt,
                        scheduledAt: $post->scheduledAt,
                        createdAt: $post->createdAt ?? '',
                        updatedAt: $post->updatedAt ?? '',
                        deletedAt: $post->deletedAt,
                    ),
                    $posts,
                )),
            'meta' => [
                'total' => $result['total'],
                'perPage' => $result['perPage'],
                'currentPage' => $result['currentPage'],
                'lastPage' => $result['lastPage'],
            ],
        ];
    }
}
