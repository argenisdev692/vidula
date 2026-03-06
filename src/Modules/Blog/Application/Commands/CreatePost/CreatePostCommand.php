<?php

declare(strict_types=1);

namespace Modules\Blog\Application\Commands\CreatePost;

use Modules\Blog\Application\DTOs\CreatePostDTO;

final readonly class CreatePostCommand
{
    public function __construct(
        public CreatePostDTO $dto,
    ) {
    }
}
