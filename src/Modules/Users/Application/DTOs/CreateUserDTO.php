<?php

declare(strict_types=1);

namespace Modules\Users\Application\DTOs;

use Spatie\LaravelData\Data;

/**
 * CreateUserDTO — Data Transfer Object for user creation.
 */
final class CreateUserDTO extends Data
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $lastName = null,
        public ?string $username = null,
        public ?string $phone = null,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $state = null,
        public ?string $country = null,
        public ?string $zipCode = null,
        public ?string $role = 'user',
    ) {
    }
}
