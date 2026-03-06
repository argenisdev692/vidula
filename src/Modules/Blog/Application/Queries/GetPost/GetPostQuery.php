<?php

declare(strict_types=1);

namespace Modules\Blog\Application\Queries\GetPost;

final readonly class GetPostQuery
{
    public function __construct(
        public string $uuid,
    ) {
    }
}
