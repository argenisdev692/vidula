<?php

declare(strict_types=1);

namespace Modules\Blog\Application\Commands\RestoreBlogCategory;

final readonly class RestoreBlogCategoryCommand
{
    public function __construct(
        public string $uuid,
    ) {
    }
}
