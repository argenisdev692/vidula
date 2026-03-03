<?php
declare(strict_types=1);
namespace Modules\Products\Application\Queries\ReadModels;
use Spatie\LaravelData\Data;

class ProductListReadModel extends Data
{
    public function __construct(
        public string $id,
        public int $user_id,
        public string $type,
        public string $title,
        public string $slug,
        public string $status,
        public float $price,
        public string $currency,
        public ?string $created_at,
        public ?string $deleted_at
    ) {}
}
