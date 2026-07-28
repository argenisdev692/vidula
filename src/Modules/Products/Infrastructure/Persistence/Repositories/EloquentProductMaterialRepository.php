<?php

declare(strict_types=1);

namespace Modules\Products\Infrastructure\Persistence\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Products\Domain\Enums\MaterialType;
use Modules\Products\Domain\Ports\ProductMaterialRepositoryPort;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductEloquentModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductMaterialEloquentModel;

final readonly class EloquentProductMaterialRepository implements ProductMaterialRepositoryPort
{
    public function listByProduct(int $productId): Collection
    {
        return ProductMaterialEloquentModel::query()
            ->where('product_id', $productId)
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->get();
    }

    public function findByUuid(string $uuid): ?ProductMaterialEloquentModel
    {
        return ProductMaterialEloquentModel::query()
            ->where('uuid', $uuid)
            ->first();
    }

    public function findByUuidForProduct(string $uuid, int $productId): ?ProductMaterialEloquentModel
    {
        return ProductMaterialEloquentModel::query()
            ->where('uuid', $uuid)
            ->where('product_id', $productId)
            ->first();
    }

    public function create(array $attributes): ProductMaterialEloquentModel
    {
        return ProductMaterialEloquentModel::query()->create($attributes);
    }

    public function update(
        ProductMaterialEloquentModel $material,
        array $attributes,
    ): ProductMaterialEloquentModel {
        $material->update($attributes);

        return $material->refresh();
    }

    public function replaceGenerated(string $productUuid, array $materials): void
    {
        DB::transaction(function () use ($productUuid, $materials): void {
            $product = ProductEloquentModel::query()->where('uuid', $productUuid)->firstOrFail();

            ProductMaterialEloquentModel::query()
                ->where('product_id', $product->id)
                ->whereNull('product_session_topic_id')
                ->whereIn('type', [MaterialType::Markdown->value, MaterialType::Pdf->value])
                ->where(function ($q): void {
                    $q->where('original_name', 'course.md')
                        ->orWhere('original_name', 'course.pdf')
                        ->orWhere('title', 'like', 'Course %');
                })
                ->delete();

            foreach ($materials as $material) {
                ProductMaterialEloquentModel::query()->create([
                    'uuid' => (string) Str::uuid7(),
                    'product_id' => $product->id,
                    'title' => $material->title,
                    'type' => $material->type->value,
                    'storage_disk' => $material->disk,
                    'path' => $material->path,
                    'original_name' => $material->originalName,
                    'mime_type' => $material->mimeType,
                    'size_bytes' => $material->sizeBytes,
                    'is_downloadable' => true,
                    'sort_order' => $material->sortOrder,
                ]);
            }
        });
    }
}
