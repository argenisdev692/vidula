<?php

declare(strict_types=1);

namespace Modules\Blog\Infrastructure\Persistence\Repositories;

use Modules\Blog\Domain\Entities\BlogCategory;
use Modules\Blog\Domain\Ports\BlogCategoryRepositoryPort;
use Modules\Blog\Infrastructure\Persistence\Eloquent\Models\BlogCategoryEloquentModel;
use Modules\Blog\Infrastructure\Persistence\Mappers\BlogCategoryMapper;

/**
 * EloquentBlogCategoryRepository — Implements BlogCategoryRepositoryPort using Eloquent.
 */
final class EloquentBlogCategoryRepository implements BlogCategoryRepositoryPort
{
    private const SELECT_COLUMNS = [
        'id',
        'uuid',
        'blog_category_name',
        'blog_category_description',
        'blog_category_image',
        'user_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function findByUuid(string $uuid): ?BlogCategory
    {
        $model = BlogCategoryEloquentModel::query()
            ->withTrashed()
            ->select(self::SELECT_COLUMNS)
            ->where('uuid', $uuid)
            ->first();

        return $model ? BlogCategoryMapper::toDomain($model) : null;
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{data: list<BlogCategory>, total: int, perPage: int, currentPage: int, lastPage: int}
     */
    public function findAllPaginated(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $query = BlogCategoryEloquentModel::query()
            ->select(self::SELECT_COLUMNS)
            ->withTrashed();

        $query->when(
            $filters['search'] ?? null,
            fn($q, $search) => $q->where(function ($q) use ($search) {
                $q->where('blog_category_name', 'like', "%{$search}%")
                    ->orWhere('blog_category_description', 'like', "%{$search}%");
            }),
        )
            ->orderBy(
                $filters['sortBy'] ?? 'created_at',
                $filters['sortDir'] ?? 'desc',
            );

        $paginator = $query->paginate(perPage: $perPage, page: $page);

        return [
            'data' => array_map(
                fn(BlogCategoryEloquentModel $model) => BlogCategoryMapper::toDomain($model),
                $paginator->items(),
            ),
            'total' => $paginator->total(),
            'perPage' => $paginator->perPage(),
            'currentPage' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): BlogCategory
    {
        $model = BlogCategoryEloquentModel::query()->create($data);

        return BlogCategoryMapper::toDomain($model);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $uuid, array $data): BlogCategory
    {
        $model = BlogCategoryEloquentModel::query()->where('uuid', $uuid)->firstOrFail();
        $model->update($data);
        $model->refresh();

        return BlogCategoryMapper::toDomain($model);
    }

    public function softDelete(string $uuid): void
    {
        $model = BlogCategoryEloquentModel::query()->where('uuid', $uuid)->firstOrFail();
        $model->delete();
    }

    public function restore(string $uuid): void
    {
        $model = BlogCategoryEloquentModel::query()->withTrashed()->where('uuid', $uuid)->firstOrFail();
        $model->restore();
    }
}
