<?php

declare(strict_types=1);

namespace Modules\Blog\Application\Commands\UpdatePost;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Blog\Domain\Entities\Post;
use Modules\Blog\Domain\Exceptions\PostNotFoundException;
use Modules\Blog\Domain\Ports\PostRepositoryPort;
use Shared\Infrastructure\Audit\AuditInterface;

final readonly class UpdatePostHandler
{
    public function __construct(
        private PostRepositoryPort $repository,
        private AuditInterface $audit,
    ) {
    }

    public function handle(UpdatePostCommand $command): Post
    {
        $existing = $this->repository->findByUuid($command->uuid);

        if ($existing === null) {
            throw PostNotFoundException::forUuid($command->uuid);
        }

        $dto = $command->dto;
        $status = $dto->postStatus ?? $existing->status;
        $title = $dto->postTitle ?? $existing->title;
        $slugSource = $dto->postTitleSlug ?? $existing->slug ?: $title;
        $slug = $slugSource
            |> trim(...)
            |> Str::slug(...);

        $post = $this->repository->update($command->uuid, [
            'post_title' => $title,
            'post_title_slug' => $slug,
            'post_content' => $dto->postContent ?? $existing->content,
            'post_excerpt' => $dto->postExcerpt ?? $existing->excerpt,
            'post_cover_image' => $dto->postCoverImage ?? $existing->coverImage,
            'meta_title' => $dto->metaTitle ?? $existing->metaTitle,
            'meta_description' => $dto->metaDescription ?? $existing->metaDescription,
            'meta_keywords' => $dto->metaKeywords ?? $existing->metaKeywords,
            'category_id' => $dto->categoryUuid !== null
                ? ($dto->categoryUuid !== '' ? $this->repository->findCategoryIdByUuid($dto->categoryUuid) : null)
                : $existing->categoryId,
            'post_status' => $status,
            'published_at' => $status === 'published'
                ? ($dto->publishedAt ?? $existing->publishedAt ?? now()->toIso8601String())
                : null,
            'scheduled_at' => $status === 'scheduled'
                ? ($dto->scheduledAt ?? $existing->scheduledAt)
                : null,
        ]);

        Cache::forget("post_read_{$command->uuid}");
        $this->invalidateListCache();

        $this->audit->log(
            logName: 'posts.updated',
            description: "Post updated: {$command->uuid}",
            properties: ['uuid' => $command->uuid],
        );

        return $post;
    }

    private function invalidateListCache(): void
    {
        try {
            Cache::tags(['posts_list'])->flush();
        } catch (\Exception) {
        }
    }
}
