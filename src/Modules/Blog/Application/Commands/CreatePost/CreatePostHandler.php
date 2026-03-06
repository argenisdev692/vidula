<?php

declare(strict_types=1);

namespace Modules\Blog\Application\Commands\CreatePost;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Blog\Domain\Entities\Post;
use Modules\Blog\Domain\Ports\PostRepositoryPort;
use Shared\Infrastructure\Audit\AuditInterface;

final readonly class CreatePostHandler
{
    public function __construct(
        private PostRepositoryPort $repository,
        private AuditInterface $audit,
    ) {
    }

    public function handle(CreatePostCommand $command): Post
    {
        $dto = $command->dto;
        $uuid = Str::uuid()->toString();
        $slug = $this->buildSlug($dto->postTitleSlug, $dto->postTitle);
        $status = $dto->postStatus;

        $post = $this->repository->create([
            'uuid' => $uuid,
            'post_title' => $dto->postTitle,
            'post_title_slug' => $slug,
            'post_content' => $dto->postContent,
            'post_excerpt' => $dto->postExcerpt,
            'post_cover_image' => $dto->postCoverImage,
            'meta_title' => $dto->metaTitle,
            'meta_description' => $dto->metaDescription,
            'meta_keywords' => $dto->metaKeywords,
            'category_id' => $dto->categoryUuid ? $this->repository->findCategoryIdByUuid($dto->categoryUuid) : null,
            'user_id' => auth()->id(),
            'post_status' => $status,
            'published_at' => $status === 'published' ? ($dto->publishedAt ?? now()->toIso8601String()) : null,
            'scheduled_at' => $status === 'scheduled' ? $dto->scheduledAt : null,
        ]);

        $this->invalidateListCache();

        $this->audit->log(
            logName: 'posts.created',
            description: "Post created: {$dto->postTitle}",
            properties: ['uuid' => $uuid, 'title' => $dto->postTitle],
        );

        return $post;
    }

    private function buildSlug(?string $candidate, string $title): string
    {
        return ($candidate ?: $title)
            |> trim(...)
            |> Str::slug(...);
    }

    private function invalidateListCache(): void
    {
        try {
            Cache::tags(['posts_list'])->flush();
        } catch (\Exception) {
        }
    }
}
