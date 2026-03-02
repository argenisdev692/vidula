<?php

declare(strict_types=1);

namespace Modules\Students\Domain\Exceptions;

use Shared\Domain\Exceptions\DomainException;

final class StudentNotFoundException extends DomainException
{
    public static function forId(string $id): self
    {
        return new self("Student with ID [{$id}] not found.");
    }

    public static function forEmail(string $email): self
    {
        return new self("Student with email [{$email}] not found.");
    }
}
