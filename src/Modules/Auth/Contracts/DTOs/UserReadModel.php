<?php

declare(strict_types=1);

namespace Modules\Auth\Contracts\DTOs;

use Spatie\LaravelData\Data;

/**
 * UserReadModel — DTO for single user read operations.
 */
final class UserReadModel extends Data
{
    public function __construct(
        public int $id,
        public string $uuid,
        public string $name,
        public ?string $lastName,
        public ?string $email,
        public ?string $username,
        public ?string $profilePhotoPath,
        public ?string $phone,
        public bool $isEmailVerified,
        public string $createdAt,
        public string $updatedAt,
        public ?string $deletedAt,
    ) {}
}
