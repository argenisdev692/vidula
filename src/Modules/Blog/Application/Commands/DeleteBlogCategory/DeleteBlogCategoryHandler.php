<?php

declare(strict_types=1);

namespace Modules\Blog\Application\Commands\DeleteBlogCategory;

use Illuminate\Support\Facades\Cache;
use Modules\Blog\Domain\Exceptions\BlogCategoryNotFoundException;
use Modules\Blog\Domain\Ports\BlogCategoryRepositoryPort;
use Shared\Infrastructure\Audit\AuditInterface;

/**
 * DeleteBlogCategoryHandler — Validates existence, then performs soft-delete via repository.
 */
final readonly class DeleteBlogCategoryHandler
{
    public function __construct(
        private BlogCategoryRepositoryPort $repository,
        private AuditInterface $audit,
    ) {
    }

    public function handle(DeleteBlogCategoryCommand $command): void
    {
        $existing = $this->repository->findByUuid($command->uuid);

        if ($existing === null) {
            throw BlogCategoryNotFoundException::forUuid($command->uuid);
        }

        $this->repository->softDelete($command->uuid);

        // Invalidate caches
        Cache::forget("blog_category_read_{$command->uuid}");
        $this->invalidateListCache();

        // Audit business action
        $this->audit->log(
            logName: 'blog_categories.deleted',
            description: "Blog category soft-deleted: {$command->uuid}",
            properties: ['uuid' => $command->uuid],
        );
    }

    private function invalidateListCache(): void
    {
        try {
            Cache::tags(['blog_categories_list'])->flush();
        } catch (\Exception $e) {
            // Tags not supported, cache will expire naturally
        }
    }
}
