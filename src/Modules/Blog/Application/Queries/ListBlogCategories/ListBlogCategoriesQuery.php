<?php

declare(strict_types=1);

namespace Modules\Blog\Application\Queries\ListBlogCategories;

use Modules\Blog\Application\DTOs\BlogCategoryFilterDTO;

final readonly class ListBlogCategoriesQuery
{
    public function __construct(
        public BlogCategoryFilterDTO $filters,
    ) {
    }
}
