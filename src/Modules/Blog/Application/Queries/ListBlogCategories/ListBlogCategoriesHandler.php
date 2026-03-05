<?php

declare(strict_types=1);

namespace Modules\Blog\Application\Queries\ListBlogCategories;

use Illuminate\Support\Facades\Cache;
use Modules\Blog\Application\DTOs\BlogCategoryFilterDTO;
use Modules\Blog\Application\Queries\ReadModels\BlogCategoryListReadModel;
use Modules\Blog\Domain\Ports\BlogCategoryRepositoryPort;

final readonly class ListBlogCategoriesHandler
{
    public function __construct(
        private BlogCategoryRepositoryPort $repository,
    ) {
    }

    /**
     * @return array{data: list<BlogCategoryListReadModel>, meta: array{total: int, perPage: int, currentPage: int, lastPage: int}}
     */
    public function handle(ListBlogCategoriesQuery $query): array
    {
        $filters = $query->filters;
        $cacheKey = "blog_categories_list_" . md5(serialize($filters->toArray()));
        $ttl = 60 * 15; // 15 minutes

        try {
            return Cache::tags(['blog_categories_list'])->remember($cacheKey, $ttl, function () use ($filters) {
                return $this->fetchAndMap($filters);
            });
        } catch (\Exception $e) {
            return Cache::remember($cacheKey, $ttl, function () use ($filters) {
                return $this->fetchAndMap($filters);
            });
        }
    }

    /**
     * @return array{data: list<BlogCategoryListReadModel>, meta: array{total: int, perPage: int, currentPage: int, lastPage: int}}
     */
    private function fetchAndMap(BlogCategoryFilterDTO $filters): array
    {
        $result = $this->repository->findAllPaginated(
            filters: $filters->toArray(),
            page: $filters->page,
            perPage: $filters->perPage,
        );

        $mapped = $result['data']
            |> (fn($categories) => array_map(self::mapToReadModel(...), $categories));

        return [
            'data' => $mapped,
            'meta' => [
                'total' => $result['total'],
                'perPage' => $result['perPage'],
                'currentPage' => $result['currentPage'],
                'lastPage' => $result['lastPage'],
            ],
        ];
    }

    private static function mapToReadModel($category): BlogCategoryListReadModel
    {
        return new BlogCategoryListReadModel(
            uuid: $category->uuid,
            blogCategoryName: $category->name,
            blogCategoryDescription: $category->description,
            blogCategoryImage: $category->image,
            createdAt: $category->createdAt ?? '',
            updatedAt: $category->updatedAt ?? '',
            deletedAt: $category->deletedAt,
        );
    }
}
