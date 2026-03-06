<?php

declare(strict_types=1);

namespace Modules\Blog\Application\Queries\GetPost;

use Illuminate\Support\Facades\Cache;
use Modules\Blog\Application\Queries\ReadModels\PostReadModel;
use Modules\Blog\Domain\Exceptions\PostNotFoundException;
use Modules\Blog\Domain\Ports\PostRepositoryPort;

final readonly class GetPostHandler
{
    public function __construct(
        private PostRepositoryPort $repository,
    ) {
    }

    public function handle(GetPostQuery $query): PostReadModel
    {
        return Cache::remember("post_read_{$query->uuid}", 60 * 15, function () use ($query) {
            $post = $this->repository->findByUuid($query->uuid);

            if ($post === null) {
                throw PostNotFoundException::forUuid($query->uuid);
            }

            return new PostReadModel(
                uuid: $post->uuid,
                postTitle: $post->title,
                postTitleSlug: $post->slug,
                postContent: $post->content,
                postExcerpt: $post->excerpt,
                postCoverImage: $post->coverImage,
                metaTitle: $post->metaTitle,
                metaDescription: $post->metaDescription,
                metaKeywords: $post->metaKeywords,
                categoryUuid: $post->categoryUuid,
                categoryName: $post->categoryName,
                userId: $post->userId,
                postStatus: $post->status,
                publishedAt: $post->publishedAt,
                scheduledAt: $post->scheduledAt,
                createdAt: $post->createdAt,
                updatedAt: $post->updatedAt,
                deletedAt: $post->deletedAt,
            );
        });
    }
}
