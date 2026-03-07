<?php

declare(strict_types=1);

namespace Modules\Students\Application\DTOs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * StudentFilterDTO
 */
#[MapInputName(SnakeCaseMapper::class)]
final class StudentFilterDTO extends Data
{
    public function __construct(
        public ?string $search = null,
        public ?string $email = null,
        public ?string $status = null,
        #[MapInputName('date_from')]
        public ?string $dateFrom = null,
        #[MapInputName('date_to')]
        public ?string $dateTo = null,
        #[MapInputName('sort_by')]
        public ?string $sortBy = 'created_at',
        public ?string $sortDir = 'desc',
        public int $page = 1,
        public int $perPage = 15,
    ) {
    }
}
