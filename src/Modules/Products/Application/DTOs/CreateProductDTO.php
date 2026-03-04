<?php
declare(strict_types=1);

namespace Modules\Products\Application\DTOs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * @OA\Schema(
 *     schema="CreateProductDTO",
 *     required={"user_id", "type", "title", "slug", "price", "currency", "level", "language"},
 *     @OA\Property(property="user_id", type="string", format="uuid"),
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
#[MapInputName(SnakeCaseMapper::class)]
final class CreateProductDTO extends Data
{
    public function __construct(
        public string $userId,
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
