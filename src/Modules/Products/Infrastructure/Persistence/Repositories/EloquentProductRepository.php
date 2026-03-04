<?php

declare(strict_types=1);

namespace Modules\Products\Infrastructure\Persistence\Repositories;

use Modules\Products\Domain\Entities\Product;
use Modules\Products\Domain\Ports\ProductRepositoryPort;
use Modules\Products\Domain\ValueObjects\ProductId;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductEloquentModel;
use Modules\Products\Infrastructure\Persistence\Mappers\ProductMapper;
use Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserEloquentModel;

/**
 * EloquentProductRepository
 */
final class EloquentProductRepository implements ProductRepositoryPort
{
    public function findById(ProductId $id): ?Product
    {
        $model = ProductEloquentModel::withTrashed()
            ->with(['user:id,uuid'])
            ->where('uuid', $id->value)
            ->first();

        return $model ? ProductMapper::toDomain($model) : null;
    }

    public function findBySlug(string $slug): ?Product
    {
        $model = ProductEloquentModel::withTrashed()
            ->with(['user:id,uuid'])
            ->where('slug', $slug)
            ->first();

        return $model ? ProductMapper::toDomain($model) : null;
    }

    public function save(Product $product): void
    {
        $model = ProductEloquentModel::withTrashed()
            ->where('uuid', $product->id->value)
            ->first() ?? new ProductEloquentModel();

        $user = UserEloquentModel::where('uuid', $product->userId->value)->firstOrFail();

        $model->fill([
            'uuid' => $product->id->value,
            'user_id' => $user->id,
            'type' => $product->type->value,
            'title' => $product->title,
            'slug' => $product->slug,
            'description' => $product->description,
            'price' => $product->price->amount,
            'currency' => $product->price->currency,
            'status' => $product->status->value,
            'thumbnail' => $product->thumbnail,
            'level' => $product->level->value,
            'language' => $product->language,
            'deleted_at' => $product->deletedAt,
        ]);

        $model->save();
    }

    public function delete(ProductId $id): void
    {
        ProductEloquentModel::query()->where('uuid', $id->value)->delete();
    }

    public function restore(ProductId $id): void
    {
        ProductEloquentModel::query()->withTrashed()->where('uuid', $id->value)->restore();
    }

    public function findAllPaginated(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $query = ProductEloquentModel::query()
            ->with(['user:id,uuid'])
            ->when($filters['type'] ?? null, fn($q, $type) => $q->where('type', $type))
            ->when($filters['status'] ?? null, fn($q, $status) => $q->where('status', $status))
            ->when($filters['level'] ?? null, fn($q, $level) => $q->where('level', $level))
            ->when($filters['language'] ?? null, fn($q, $lang) => $q->where('language', $lang))
            ->when(
                $filters['search'] ?? null,
                fn($q, $search) =>
                $q->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                })
            )
            ->orderBy($filters['sort_by'] ?? 'created_at', $filters['sort_dir'] ?? 'desc');

        $paginator = $query->paginate(perPage: $perPage, page: $page);

        return [
            'data' => array_map(
                fn(ProductEloquentModel $model) => ProductMapper::toDomain($model),
                $paginator->items()
            ),
            'total' => $paginator->total(),
            'perPage' => $paginator->perPage(),
            'currentPage' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
        ];
    }
}
