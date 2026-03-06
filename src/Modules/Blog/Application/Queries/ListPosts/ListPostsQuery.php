<?php

declare(strict_types=1);

namespace Modules\Blog\Application\Queries\ListPosts;

use Modules\Blog\Application\DTOs\PostFilterDTO;

final readonly class ListPostsQuery
{
    public function __construct(
        public PostFilterDTO $filters,
    ) {
    }
}
