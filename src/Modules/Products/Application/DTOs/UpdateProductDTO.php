<?php
declare(strict_types=1);

namespace Modules\Products\Application\DTOs;

use Spatie\LaravelData\Data;

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
    ) {}
}
