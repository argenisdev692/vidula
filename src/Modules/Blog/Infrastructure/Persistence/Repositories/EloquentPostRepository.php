<?php

declare(strict_types=1);

namespace Modules\Blog\Infrastructure\Persistence\Repositories;

use Modules\Blog\Domain\Entities\Post;
use Modules\Blog\Domain\Ports\PostRepositoryPort;
use Modules\Blog\Infrastructure\Persistence\Eloquent\Models\BlogCategoryEloquentModel;
use Modules\Blog\Infrastructure\Persistence\Eloquent\Models\PostEloquentModel;
use Modules\Blog\Infrastructure\Persistence\Mappers\PostMapper;

final class EloquentPostRepository implements PostRepositoryPort
{
    private const SELECT_COLUMNS = [
        'id',
        'uuid',
        'post_title',
        'post_title_slug',
        'post_content',
        'post_excerpt',
        'post_cover_image',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'category_id',
        'user_id',
        'post_status',
        'published_at',
        'scheduled_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function findByUuid(string $uuid): ?Post
    {
        $model = PostEloquentModel::query()
            ->withTrashed()
            ->with(['category:id,uuid,blog_category_name'])
            ->select(self::SELECT_COLUMNS)
            ->where('uuid', $uuid)
            ->first();

        return $model ? PostMapper::toDomain($model) : null;
    }

    public function findCategoryIdByUuid(string $uuid): ?int
    {
        return BlogCategoryEloquentModel::query()
            ->select(['id'])
            ->where('uuid', $uuid)
            ->value('id');
    }

    public function findAllPaginated(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $query = PostEloquentModel::query()
            ->withTrashed()
            ->with(['category:id,uuid,blog_category_name'])
            ->select(self::SELECT_COLUMNS);

        $status = $filters['status'] ?? null;

        $query->when(
            $filters['search'] ?? null,
            fn($q, $search) => $q->where(function ($subQuery) use ($search): void {
                $subQuery
                    ->where('post_title', 'like', "%{$search}%")
                    ->orWhere('post_excerpt', 'like', "%{$search}%")
                    ->orWhere('post_content', 'like', "%{$search}%");
            }),
        )->when(
            $status === 'deleted',
            fn($q) => $q->whereNotNull('deleted_at'),
            fn($q) => $q
                ->when(
                    $status === 'active' || $status === null,
                    fn($activeQuery) => $activeQuery->whereNull('deleted_at'),
                )
                ->when(
                    in_array($status, ['draft', 'published', 'scheduled', 'archived'], true),
                    fn($statusQuery) => $statusQuery
                        ->whereNull('deleted_at')
                        ->where('post_status', $status),
                ),
        )->when(
            $filters['dateFrom'] ?? null,
            fn($q, $dateFrom) => $q->whereDate('created_at', '>=', $dateFrom),
        )->when(
            $filters['dateTo'] ?? null,
            fn($q, $dateTo) => $q->whereDate('created_at', '<=', $dateTo),
        )->orderBy(
            $filters['sortBy'] ?? 'created_at',
            $filters['sortDir'] ?? 'desc',
        );

        $paginator = $query->paginate(perPage: $perPage, page: $page);

        return [
            'data' => array_map(
                fn(PostEloquentModel $model) => PostMapper::toDomain($model),
                $paginator->items(),
            ),
            'total' => $paginator->total(),
            'perPage' => $paginator->perPage(),
            'currentPage' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
        ];
    }

    public function create(array $data): Post
    {
        $model = PostEloquentModel::query()->create($data);
        $model->loadMissing(['category:id,uuid,blog_category_name']);

        return PostMapper::toDomain($model);
    }

    public function update(string $uuid, array $data): Post
    {
        $model = PostEloquentModel::query()->where('uuid', $uuid)->firstOrFail();
        $payload = array_filter(
            $data,
            fn(mixed $value, string $key): bool => $value !== null || $key === 'category_id',
            ARRAY_FILTER_USE_BOTH,
        );
        $model->update($payload);
        $model->refresh()->loadMissing(['category:id,uuid,blog_category_name']);

        return PostMapper::toDomain($model);
    }

    public function softDelete(string $uuid): void
    {
        PostEloquentModel::query()->where('uuid', $uuid)->firstOrFail()->delete();
    }

    public function restore(string $uuid): void
    {
        PostEloquentModel::query()->withTrashed()->where('uuid', $uuid)->firstOrFail()->restore();
    }
}
