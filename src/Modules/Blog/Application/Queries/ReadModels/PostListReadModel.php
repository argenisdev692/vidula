<?php

declare(strict_types=1);

namespace Modules\Blog\Application\Queries\ReadModels;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapOutputName(SnakeCaseMapper::class)]
final class PostListReadModel extends Data
{
    public function __construct(
        public string $uuid,
        public string $postTitle,
        public string $postTitleSlug,
        public ?string $postExcerpt,
        public ?string $postCoverImage,
        public ?string $categoryName,
        public string $postStatus,
        public ?string $publishedAt,
        public ?string $scheduledAt,
        public string $createdAt,
        public string $updatedAt,
        public ?string $deletedAt,
    ) {
    }
}
