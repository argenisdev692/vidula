<?php

declare(strict_types=1);

namespace Modules\Blog\Application\Commands\DeleteBlogCategory;

final readonly class DeleteBlogCategoryCommand
{
    public function __construct(
        public string $uuid,
    ) {
    }
}
