<?php

declare(strict_types=1);

namespace Modules\Blog\Application\Commands\DeletePost;

use Illuminate\Support\Facades\Cache;
use Modules\Blog\Domain\Exceptions\PostNotFoundException;
use Modules\Blog\Domain\Ports\PostRepositoryPort;
use Shared\Infrastructure\Audit\AuditInterface;

final readonly class DeletePostHandler
{
    public function __construct(
        private PostRepositoryPort $repository,
        private AuditInterface $audit,
    ) {
    }

    public function handle(DeletePostCommand $command): void
    {
        $existing = $this->repository->findByUuid($command->uuid);

        if ($existing === null) {
            throw PostNotFoundException::forUuid($command->uuid);
        }

        $this->repository->softDelete($command->uuid);

        Cache::forget("post_read_{$command->uuid}");
        $this->invalidateListCache();

        $this->audit->log(
            logName: 'posts.deleted',
            description: "Post soft-deleted: {$command->uuid}",
            properties: ['uuid' => $command->uuid],
        );
    }

    private function invalidateListCache(): void
    {
        try {
            Cache::tags(['posts_list'])->flush();
        } catch (\Exception) {
        }
    }
}
