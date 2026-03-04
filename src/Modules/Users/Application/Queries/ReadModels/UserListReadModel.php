<?php

declare(strict_types=1);

namespace Modules\Users\Application\Queries\ReadModels;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * UserListReadModel — Optimized for index tables.
 */
#[MapOutputName(SnakeCaseMapper::class)]
final class UserListReadModel extends Data
{
    public function __construct(
        public string $uuid,
        public string $name,
        public string $lastName,
        public string $fullName,
        public string $email,
        public ?string $username,
        public ?string $phone,
        public string $status,
        public ?string $profilePhotoPath,
        public ?string $role,
        public string $createdAt,
        public string $updatedAt,
        public ?string $deletedAt,
    ) {
    }
}
