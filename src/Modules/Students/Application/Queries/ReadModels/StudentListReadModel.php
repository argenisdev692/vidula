<?php

declare(strict_types=1);

namespace Modules\Students\Application\Queries\ReadModels;

use Spatie\LaravelData\Data;

final class StudentListReadModel extends Data
{
    public function __construct(
        public string $uuid,
        public string $name,
        public ?string $email,
        public ?string $phone,
        public string $status,
        public bool $active,
        public ?string $createdAt,
        public ?string $deletedAt
    ) {
    }
}
