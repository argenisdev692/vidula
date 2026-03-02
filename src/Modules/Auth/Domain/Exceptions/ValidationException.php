<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Exceptions;

use Shared\Domain\Exceptions\DomainException;

/**
 * ValidationException — Thrown when validation fails.
 */
final class ValidationException extends DomainException
{
    public static function withMessage(string $message): self
    {
        return new self($message);
    }

    public static function withField(string $field, string $message): self
    {
        return new self("Validation failed for {$field}: {$message}");
    }
}
