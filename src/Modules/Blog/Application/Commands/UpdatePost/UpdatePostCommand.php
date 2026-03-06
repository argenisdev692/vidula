<?php

declare(strict_types=1);

namespace Modules\Blog\Application\Commands\UpdatePost;

use Modules\Blog\Application\DTOs\UpdatePostDTO;

final readonly class UpdatePostCommand
{
    public function __construct(
        public string $uuid,
        public UpdatePostDTO $dto,
    ) {
    }
}
