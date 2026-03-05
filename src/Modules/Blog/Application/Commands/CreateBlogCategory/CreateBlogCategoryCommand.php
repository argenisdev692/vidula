<?php

declare(strict_types=1);

namespace Modules\Blog\Application\Commands\CreateBlogCategory;

use Modules\Blog\Application\DTOs\CreateBlogCategoryDTO;

final readonly class CreateBlogCategoryCommand
{
    public function __construct(
        public CreateBlogCategoryDTO $dto,
    ) {
    }
}
