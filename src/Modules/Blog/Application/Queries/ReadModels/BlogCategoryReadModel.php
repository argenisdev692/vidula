<?php

declare(strict_types=1);

namespace Modules\Blog\Application\Queries\ReadModels;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * BlogCategoryReadModel — Detail view for a single blog category.
 */
#[MapOutputName(SnakeCaseMapper::class)]
final class BlogCategoryReadModel extends Data
{
    public function __construct(
        public string $uuid,
        public string $blogCategoryName,
        public ?string $blogCategoryDescription,
        public ?string $blogCategoryImage,
        public ?int $userId,
        public ?string $createdAt,
        public ?string $updatedAt,
        public ?string $deletedAt,
    ) {
    }
}
