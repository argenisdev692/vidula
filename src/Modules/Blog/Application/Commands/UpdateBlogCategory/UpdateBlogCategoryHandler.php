<?php

declare(strict_types=1);

namespace Modules\Blog\Application\Commands\UpdateBlogCategory;

use Illuminate\Support\Facades\Cache;
use Modules\Blog\Domain\Entities\BlogCategory;
use Modules\Blog\Domain\Exceptions\BlogCategoryNotFoundException;
use Modules\Blog\Domain\Ports\BlogCategoryRepositoryPort;
use Shared\Infrastructure\Audit\AuditInterface;

/**
 * UpdateBlogCategoryHandler — Validates existence, delegates update to repository.
 */
final readonly class UpdateBlogCategoryHandler
{
    public function __construct(
        private BlogCategoryRepositoryPort $repository,
        private AuditInterface $audit,
    ) {
    }

    public function handle(UpdateBlogCategoryCommand $command): BlogCategory
    {
        $existing = $this->repository->findByUuid($command->uuid);

        if ($existing === null) {
            throw BlogCategoryNotFoundException::forUuid($command->uuid);
        }

        $category = $this->repository->update($command->uuid, $command->dto->toArray());

        // Invalidate caches
        Cache::forget("blog_category_read_{$command->uuid}");
        $this->invalidateListCache();

        // Audit business action
        $this->audit->log(
            logName: 'blog_categories.updated',
            description: "Blog category updated: {$command->uuid}",
            properties: ['uuid' => $command->uuid, 'changes' => $command->dto->toArray()],
        );

        return $category;
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
