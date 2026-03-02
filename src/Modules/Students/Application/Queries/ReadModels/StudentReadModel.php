<?php
declare(strict_types=1);

namespace Modules\Students\Application\Queries\ReadModels;

use Spatie\LaravelData\Data;

final class StudentReadModel extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $email,
        public ?string $phone,
        public ?string $dni,
        public ?string $birthDate,
        public ?string $address,
        public ?string $avatar,
        public ?string $notes,
        public bool $active,
        public ?string $createdAt,
        public ?string $updatedAt,
        public ?string $deletedAt
    ) {}
}
