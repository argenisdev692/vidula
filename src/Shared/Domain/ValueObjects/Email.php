<?php

declare(strict_types=1);

namespace Shared\Domain\ValueObjects;

use Shared\Domain\Exceptions\DomainException;

readonly class Email
{
    public string $value;

    public function __construct(string $value)
    {
        $this->ensureIsValid($value);
        $this->value = strtolower(trim($value));
    }

    private function ensureIsValid(string $value): void
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \Shared\Domain\Exceptions\ValidationException("Invalid Email: {$value}");
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
