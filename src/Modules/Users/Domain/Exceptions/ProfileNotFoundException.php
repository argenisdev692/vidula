<?php

declare(strict_types=1);

namespace Modules\Users\Domain\Exceptions;

use Shared\Domain\Exceptions\DomainException;

/**
 * ProfileNotFoundException
 */
final class ProfileNotFoundException extends DomainException
{
    public static function forUser(string $userId): self
    {
        return new self("Profile not found for user ID: {$userId}");
    }
}
