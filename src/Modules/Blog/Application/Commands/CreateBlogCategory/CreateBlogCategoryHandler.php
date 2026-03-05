<?php

declare(strict_types=1);

namespace Modules\Blog\Application\Commands\CreateBlogCategory;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Modules\Blog\Domain\Entities\BlogCategory;
use Modules\Blog\Domain\Ports\BlogCategoryRepositoryPort;
use Shared\Infrastructure\Audit\AuditInterface;

final readonly class CreateBlogCategoryHandler
{
    public function __construct(
        private BlogCategoryRepositoryPort $repository,
        private AuditInterface $audit,
    ) {
    }

    public function handle(CreateBlogCategoryCommand $command): BlogCategory
    {
        $dto = $command->dto;
        $uuid = Str::uuid()->toString();

        $category = $this->repository->create([
            'uuid' => $uuid,
            'blog_category_name' => $dto->blogCategoryName,
            'blog_category_description' => $dto->blogCategoryDescription,
            'blog_category_image' => $dto->blogCategoryImage,
            'user_id' => auth()->id(),
        ]);

        // Invalidate list cache
        $this->invalidateListCache();

        // Audit business action
        $this->audit->log(
            logName: 'blog_categories.created',
            description: "Blog category created: {$dto->blogCategoryName}",
            properties: ['uuid' => $uuid, 'name' => $dto->blogCategoryName],
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
