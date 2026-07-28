<?php

declare(strict_types=1);

namespace Modules\Products\Application\Commands;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Clients\Domain\Ports\ClientRepositoryPort;
use Modules\Products\Application\DTOs\ProductData;
use Modules\Products\Application\Services\ProductSlugFactory;
use Modules\Products\Domain\Ports\ProductRepositoryPort;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductEloquentModel;

/**
 * Updates the catalog row and re-syncs the 1:1 detail. The slug only moves when
 * the title actually changed, so existing links keep resolving on a no-op save.
 */
final readonly class UpdateProductHandler
{
    public function __construct(
        private ProductRepositoryPort $products,
        private ClientRepositoryPort $clients,
    ) {}

    #[\NoDiscard]
    public function handle(ProductEloquentModel $product, ProductData $data): ProductEloquentModel
    {
        $clientId = $data->clientUuid !== null
            ? $this->clients->findByUuid($data->clientUuid)?->id
            : null;

        return DB::transaction(function () use ($product, $data, $clientId): ProductEloquentModel {
            $updated = $this->products->update($product, [
                ...$data->toProductAttributes(),
                'slug' => $this->slugFor($product, $data),
                'client_id' => $clientId,
            ]);

            $detail = $data->toDetailAttributes();

            if ($detail !== null) {
                $this->products->saveDetail($updated, $detail);
            }

            return $updated;
        });
    }

    private function slugFor(ProductEloquentModel $product, ProductData $data): string
    {
        if ($product->title === $data->title) {
            return $product->slug;
        }

        $base = $data->title
            |> trim(...)
            |> Str::slug(...)
            |> ProductSlugFactory::fallbackWhenBlank(...);

        if (! $this->products->slugExists($base, $product->uuid)) {
            return $base;
        }

        foreach (range(2, ProductSlugFactory::MAX_SUFFIX_ATTEMPTS) as $suffix) {
            $candidate = "{$base}-{$suffix}";

            if (! $this->products->slugExists($candidate, $product->uuid)) {
                return $candidate;
            }
        }

        return $base.'-'.Str::lower(Str::random(8));
    }
}
