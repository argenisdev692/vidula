<?php

declare(strict_types=1);

namespace Modules\Clients\Domain\Exceptions;

use Shared\Domain\Exceptions\DomainException;

final class ClientNotFoundException extends DomainException
{
    public static function forUser(string $userId): self
    {
        return new self("Client for user [{$userId}] not found.");
    }

    public static function forId(string $id): self
    {
        return new self("Client with ID [{$id}] not found.");
    }
}
