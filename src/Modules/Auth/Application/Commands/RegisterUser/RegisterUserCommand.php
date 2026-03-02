<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Commands\RegisterUser;

/**
 * RegisterUserCommand — Command to register a new user.
 */
final readonly class RegisterUserCommand
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public ?string $lastName = null,
        public ?string $username = null,
        public ?string $phone = null,
    ) {}
}
