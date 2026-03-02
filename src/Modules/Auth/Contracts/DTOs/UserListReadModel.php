<?php

declare(strict_types=1);

namespace Modules\Auth\Contracts\DTOs;

use Spatie\LaravelData\Data;

/**
 * UserListReadModel — DTO for user list operations.
 */
final class UserListReadModel extends Data
{
    public function __construct(
        public int $id,
        public string $uuid,
        public string $name,
        public ?string $lastName,
        public ?string $email,
        public ?string $username,
        public ?string $profilePhotoPath,
        public bool $isEmailVerified,
        public string $createdAt,
    ) {}
}
