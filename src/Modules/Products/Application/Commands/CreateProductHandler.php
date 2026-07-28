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
 * Creates the catalog row plus the thin 1:1 detail implied by `type`
 * (classrooms for `classroom`, video_courses for the video types) in one
 * transaction, so a product never exists without its detail.
 */
final readonly class CreateProductHandler
{
    public function __construct(
        private ProductRepositoryPort $products,
        private ClientRepositoryPort $clients,
    ) {}

    #[\NoDiscard]
    public function handle(ProductData $data, int $userId): ProductEloquentModel
    {
        $slug = $data->title
            |> trim(...)
            |> Str::slug(...)
            |> ProductSlugFactory::fallbackWhenBlank(...)
            |> $this->uniqueSlug(...);

        $clientId = $data->clientUuid !== null
            ? $this->clients->findByUuid($data->clientUuid)?->id
            : null;

        return DB::transaction(function () use ($data, $userId, $slug, $clientId): ProductEloquentModel {
            $product = $this->products->create([
                ...$data->toProductAttributes(),
                'slug' => $slug,
                'user_id' => $userId,
                'client_id' => $clientId,
            ]);

            $detail = $data->toDetailAttributes();

            if ($detail !== null) {
                $this->products->saveDetail($product, $detail);
            }

            return $product;
        });
    }

    /**
     * Appends `-2`, `-3`, … until the slug is free. Bounded so a pathological
     * title can never spin the loop (OWASP — unbounded resource consumption);
     * the random suffix is the guaranteed-terminating escape hatch.
     */
    private function uniqueSlug(string $base): string
    {
        if (! $this->products->slugExists($base)) {
            return $base;
        }

        foreach (range(2, ProductSlugFactory::MAX_SUFFIX_ATTEMPTS) as $suffix) {
            $candidate = "{$base}-{$suffix}";

            if (! $this->products->slugExists($candidate)) {
                return $candidate;
            }
        }

        return $base.'-'.Str::lower(Str::random(8));
    }
}
