<?php

declare(strict_types=1);

namespace Modules\Users\Application\DTOs;

use Spatie\LaravelData\Data;
use Modules\Users\Domain\Enums\UserStatus;

/**
 * UserFilterDTO
 */
final class UserFilterDTO extends Data
{
    public function __construct(
        public ?string $search = null,
        public ?UserStatus $status = null,
        public ?string $role = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public ?string $sortBy = 'created_at',
        public ?string $sortDir = 'desc',
        public int $page = 1,
        public int $perPage = 15,
    ) {
    }
}
