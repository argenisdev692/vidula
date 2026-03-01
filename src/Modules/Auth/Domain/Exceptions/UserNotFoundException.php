<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Exceptions;

final class UserNotFoundException extends \DomainException
{
    public static function withIdentifier(string $identifier): self
    {
        return new self("No user found with identifier: {$identifier}");
    }
}
