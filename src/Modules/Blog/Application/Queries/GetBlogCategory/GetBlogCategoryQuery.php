<?php

declare(strict_types=1);

namespace Modules\Blog\Application\Queries\GetBlogCategory;

/**
 * GetBlogCategoryQuery — Fetch a single blog category by UUID.
 */
final readonly class GetBlogCategoryQuery
{
    public function __construct(
        public string $uuid,
    ) {
    }
}
