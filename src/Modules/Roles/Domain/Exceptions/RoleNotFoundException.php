<?php

declare(strict_types=1);

namespace Modules\Roles\Domain\Exceptions;

use Shared\Domain\Exceptions\DomainException;

final class RoleNotFoundException extends DomainException
{
    public static function forUuid(string $uuid): self
    {
        return new self("Role with UUID [{$uuid}] not found.");
    }
}
