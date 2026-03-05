<?php

declare(strict_types=1);

namespace Modules\Blog\Application\Commands\RestoreBlogCategory;

use Illuminate\Support\Facades\Cache;
use Modules\Blog\Domain\Ports\BlogCategoryRepositoryPort;

/**
 * RestoreBlogCategoryHandler — Restores a soft-deleted blog category.
 */
final readonly class RestoreBlogCategoryHandler
{
    public function __construct(
        private BlogCategoryRepositoryPort $repository,
    ) {
    }

    public function handle(RestoreBlogCategoryCommand $command): void
    {
        $this->repository->restore($command->uuid);

        // Clear individual cache
        Cache::forget("blog_category_read_{$command->uuid}");

        // Clear list cache
        try {
            Cache::tags(['blog_categories_list'])->flush();
        } catch (\Exception $e) {
            // Tags not supported, cache will expire naturally
        }
    }
}
