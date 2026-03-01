<?php

declare(strict_types=1);

namespace Modules\CompanyData\Domain\Exceptions;

use Shared\Domain\Exceptions\DomainException;

final class CompanyDataNotFoundException extends DomainException
{
    public static function forUser(string $userId): self
    {
        return new self("Company data for user [{$userId}] not found.");
    }

    public static function forId(string $id): self
    {
        return new self("Company data with ID [{$id}] not found.");
    }
}
