<?php
declare(strict_types=1);

namespace Modules\Products\Infrastructure\Persistence\Mappers;

use Modules\Products\Domain\Entities\Product;
use Modules\Products\Domain\Enums\ProductLevel;
use Modules\Products\Domain\Enums\ProductStatus;
use Modules\Products\Domain\Enums\ProductType;
use Modules\Products\Domain\ValueObjects\Money;
use Modules\Products\Domain\ValueObjects\ProductId;
use Modules\Products\Domain\ValueObjects\UserId;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductEloquentModel;

final class ProductMapper
{
    #[\NoDiscard]
    public static function toDomain(ProductEloquentModel $model): Product
    {
        return $model
            |> (fn($m) => [
                'id' => new ProductId($m->uuid),
                'userId' => new UserId($m->user?->uuid ?? ''),
                'type' => ProductType::from($m->type),
                'title' => $m->title,
                'slug' => $m->slug,
                'description' => $m->description,
                'price' => new Money((float) $m->price, $m->currency),
                'status' => ProductStatus::from($m->status),
                'thumbnail' => $m->thumbnail,
                'level' => ProductLevel::from($m->level),
                'language' => $m->language,
                'createdAt' => $m->created_at?->toIso8601String(),
                'updatedAt' => $m->updated_at?->toIso8601String(),
                'deletedAt' => $m->deleted_at?->toIso8601String()
            ])
            |> (fn($data) => new Product(...$data));
    }
}
