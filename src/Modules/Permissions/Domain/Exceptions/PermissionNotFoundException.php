<?php

declare(strict_types=1);

namespace Modules\Permissions\Domain\Exceptions;

use DomainException;

final class PermissionNotFoundException extends DomainException
{
    public static function forUuid(string $uuid): self
    {
        return new self("Permission with uuid [{$uuid}] was not found.");
    }
}
