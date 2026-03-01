<?php

declare(strict_types=1);

namespace Modules\Users\Application\Commands\DeleteUser;

/**
 * DeleteUserCommand — CQRS command for soft-deleting a user by UUID.
 */
final readonly class DeleteUserCommand
{
    public function __construct(
        public string $uuid,
    ) {
    }
}
