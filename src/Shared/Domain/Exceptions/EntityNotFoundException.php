<?php

declare(strict_types=1);

namespace Shared\Domain\Exceptions;

final class EntityNotFoundException extends DomainException
{
    public static function withId(string $id, string $entityName): self
    {
        return new self("{$entityName} with ID {$id} was not found.");
    }
}
