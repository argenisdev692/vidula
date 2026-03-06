<?php

declare(strict_types=1);

namespace Modules\Blog\Application\DTOs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
final class CreatePostDTO extends Data
{
    public function __construct(
        public string $postTitle,
        public string $postContent,
        public ?string $postTitleSlug = null,
        public ?string $postExcerpt = null,
        public ?string $postCoverImage = null,
        public ?string $metaTitle = null,
        public ?string $metaDescription = null,
        public ?string $metaKeywords = null,
        public ?string $categoryUuid = null,
        public string $postStatus = 'draft',
        public ?string $publishedAt = null,
        public ?string $scheduledAt = null,
    ) {
    }
}
