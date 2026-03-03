<?php
declare(strict_types=1);

namespace Modules\Products\Application\DTOs;

use Spatie\LaravelData\Data;

/**
 * @OA\Schema(
 *     schema="UpdateProductDTO",
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
final class UpdateProductDTO extends Data
{
    public function __construct(
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
