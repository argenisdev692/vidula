<?php

declare(strict_types=1);

namespace Modules\Blog\Application\Commands\UpdateBlogCategory;

use Modules\Blog\Application\DTOs\UpdateBlogCategoryDTO;

/**
 * UpdateBlogCategoryCommand — CQRS command for updating a blog category by UUID.
 */
final readonly class UpdateBlogCategoryCommand
{
    public function __construct(
        public string $uuid,
        public UpdateBlogCategoryDTO $dto,
    ) {
    }
}
