<?php

declare(strict_types=1);

namespace Modules\Clients\Application\DTOs;

use Spatie\LaravelData\Data;

/**
 * ClientFilterDTO
 */
final class ClientFilterDTO extends Data
{
    public function __construct(
        public ?string $search = null,
        public ?string $userUuid = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public ?string $sortBy = 'created_at',
        public ?string $sortDir = 'desc',
        public int $page = 1,
        public int $perPage = 15,
    ) {
    }
}
