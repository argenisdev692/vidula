<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Commands\UpdateUser;

/**
 * UpdateUserCommand — Command to update user profile.
 */
final readonly class UpdateUserCommand
{
    public function __construct(
        public int $userId,
        public string $name,
        public ?string $lastName = null,
        public ?string $phone = null,
        public ?string $username = null,
    ) {}
}
