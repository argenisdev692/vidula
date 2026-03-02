<?php
declare(strict_types=1);

namespace Modules\Products\Application\Queries\ReadModels;

use Spatie\LaravelData\Data;

final class ProductReadModel extends Data
{
    public function __construct(
        public string $id,
        public int $userId,
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
    ) {}
}
