<?php

declare(strict_types=1);

namespace Modules\Blog\Application\Commands\DeletePost;

final readonly class DeletePostCommand
{
    public function __construct(
        public string $uuid,
    ) {
    }
}
