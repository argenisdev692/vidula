<?php

declare(strict_types=1);

namespace Modules\Roles\Domain\Exceptions;

use Shared\Domain\Exceptions\DomainException;

final class ProtectedRoleException extends DomainException
{
    public static function cannotDelete(string $name): self
    {
        return new self("Role [{$name}] cannot be deleted.");
    }
}
