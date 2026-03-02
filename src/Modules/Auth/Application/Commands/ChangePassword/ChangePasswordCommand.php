<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Commands\ChangePassword;

/**
 * ChangePasswordCommand — Command to change user password.
 */
final readonly class ChangePasswordCommand
{
    public function __construct(
        public int $userId,
        public string $currentPassword,
        public string $newPassword,
    ) {}
}
