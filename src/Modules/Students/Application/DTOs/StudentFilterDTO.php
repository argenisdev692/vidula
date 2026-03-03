<?php

declare(strict_types=1);

namespace Modules\Students\Application\DTOs;

use Spatie\LaravelData\Data;

/**
 * StudentFilterDTO
 */
final class StudentFilterDTO extends Data
{
    public function __construct(
        public ?string $search = null,
        public ?string $email = null,
        public ?string $status = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public ?string $sortBy = 'created_at',
        public ?string $sortDir = 'desc',
        public int $page = 1,
        public int $perPage = 15,
    ) {
    }
}
