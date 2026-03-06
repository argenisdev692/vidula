<?php

declare(strict_types=1);

namespace Modules\Blog\Application\Commands\RestorePost;

use Illuminate\Support\Facades\Cache;
use Modules\Blog\Domain\Ports\PostRepositoryPort;

final readonly class RestorePostHandler
{
    public function __construct(
        private PostRepositoryPort $repository,
    ) {
    }

    public function handle(RestorePostCommand $command): void
    {
        $this->repository->restore($command->uuid);

        Cache::forget("post_read_{$command->uuid}");

        try {
            Cache::tags(['posts_list'])->flush();
        } catch (\Exception) {
        }
    }
}
