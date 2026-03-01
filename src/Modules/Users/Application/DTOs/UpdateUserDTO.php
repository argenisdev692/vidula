<?php

declare(strict_types=1);

namespace Modules\Users\Application\DTOs;

use Spatie\LaravelData\Data;
use Modules\Users\Domain\Enums\UserStatus;

/**
 * UpdateUserDTO
 */
final class UpdateUserDTO extends Data
{
    public function __construct(
        public ?string $name = null,
        public ?string $lastName = null,
        public ?string $email = null,
        public ?string $username = null,
        public ?string $phone = null,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $state = null,
        public ?string $country = null,
        public ?string $zipCode = null,
        public ?UserStatus $status = null,
        public ?string $role = null,
    ) {
    }
}
