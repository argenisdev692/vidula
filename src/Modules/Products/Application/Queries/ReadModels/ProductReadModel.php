<?php
declare(strict_types=1);

namespace Modules\Products\Application\Queries\ReadModels;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * @OA\Schema(
 *     schema="ProductListItem",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="user_id", type="string", format="uuid"),
 *     @OA\Property(property="type", type="string"),
 *     @OA\Property(property="title", type="string"),
 *     @OA\Property(property="slug", type="string"),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="price", type="number"),
 *     @OA\Property(property="currency", type="string"),
 *     @OA\Property(property="status", type="string"),
 *     @OA\Property(property="thumbnail", type="string", nullable=true),
 *     @OA\Property(property="level", type="string"),
 *     @OA\Property(property="language", type="string"),
 *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true)
 * )
 */
#[MapOutputName(SnakeCaseMapper::class)]
final class ProductReadModel extends Data
{
    public function __construct(
        public string $id,
        public string $userId,
        public string $type,
        public string $title,
        public string $slug,
        public ?string $description,
        public float $price,
        public string $currency,
        public string $status,
        public ?string $thumbnail,
        public string $level,
        public string $language,
        public ?string $createdAt,
        public ?string $updatedAt,
        public ?string $deletedAt
    ) {
    }
}
