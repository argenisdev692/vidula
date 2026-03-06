<?php

declare(strict_types=1);

namespace Modules\Blog\Application\Commands\RestorePost;

final readonly class RestorePostCommand
{
    public function __construct(
        public string $uuid,
    ) {
    }
}
