<?php

declare(strict_types=1);

namespace Modules\Users\Application\Queries\ReadModels;

use Spatie\LaravelData\Data;

/**
 * UserReadModel — Detailed view for single user.
 */
final class UserReadModel extends Data
{
    public function __construct(
        public string $uuid,
        public string $name,
        public string $lastName,
        public string $email,
        public ?string $username,
        public ?string $phone,
        public string $status,
        public ?string $profilePhotoPath,
        public ?string $address,
        public ?string $city,
        public ?string $state,
        public ?string $country,
        public ?string $zipCode,
        public ?string $createdAt,
        public ?string $updatedAt,
        public ?array $roles = [],
        public ?array $permissions = [],
    ) {
    }
}
