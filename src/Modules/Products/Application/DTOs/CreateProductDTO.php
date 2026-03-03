<?php
declare(strict_types=1);

namespace Modules\Products\Application\DTOs;

use Spatie\LaravelData\Data;

/**
 * @OA\Schema(
 *     schema="CreateProductDTO",
 *     required={"userId", "type", "title", "slug", "price", "currency", "level", "language"},
 *     @OA\Property(property="userId", type="integer"),
 *     @OA\Property(property="type", type="string"),
 *     @OA\Property(property="title", type="string"),
 *     @OA\Property(property="slug", type="string"),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="price", type="number"),
 *     @OA\Property(property="currency", type="string"),
 *     @OA\Property(property="level", type="string"),
 *     @OA\Property(property="language", type="string"),
 *     @OA\Property(property="thumbnail", type="string", nullable=true)
 * )
 */
final class CreateProductDTO extends Data
{
    public function __construct(
        public int $userId,
        public string $type,
        public string $title,
        public string $slug,
        public ?string $description,
        public float $price,
        public string $currency,
        public string $level,
        public string $language,
        public ?string $thumbnail = null
    ) {
    }
}
