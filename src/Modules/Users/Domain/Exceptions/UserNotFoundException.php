<?php

declare(strict_types=1);

namespace Modules\Users\Domain\Exceptions;

use Shared\Domain\Exceptions\DomainException;

final class UserNotFoundException extends DomainException
{
    public static function forId(int $id): self
    {
        return new self("User with ID [{$id}] not found.");
    }

    public static function forUuid(string $uuid): self
    {
        return new self("User with UUID [{$uuid}] not found.");
    }

    public static function forEmail(string $email): self
    {
        return new self("User with email [{$email}] not found.");
    }
}
