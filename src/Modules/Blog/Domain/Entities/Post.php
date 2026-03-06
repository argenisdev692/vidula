<?php

declare(strict_types=1);

namespace Modules\Blog\Domain\Entities;

use Modules\Blog\Domain\ValueObjects\PostId;
use Shared\Domain\Entities\AggregateRoot;

final class Post extends AggregateRoot
{
    public function __construct(
        public PostId $id,
        public string $uuid,
        public string $title,
        public string $slug,
        public string $content,
        public ?string $excerpt = null,
        public ?string $coverImage = null,
        public ?string $metaTitle = null,
        public ?string $metaDescription = null,
        public ?string $metaKeywords = null,
        public ?int $categoryId = null,
        public ?string $categoryUuid = null,
        public ?string $categoryName = null,
        public ?int $userId = null,
        public string $status = 'draft',
        public ?string $publishedAt = null,
        public ?string $scheduledAt = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public ?string $deletedAt = null,
    ) {
    }
}
