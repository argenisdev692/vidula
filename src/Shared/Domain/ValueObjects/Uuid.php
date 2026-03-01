<?php

declare(strict_types=1);

namespace Shared\Domain\ValueObjects;

use Ramsey\Uuid\Uuid as RamseyUuid;
use Shared\Domain\Exceptions\DomainException;

readonly class Uuid
{
    public string $value;

    public function __construct(string $value)
    {
        $this->ensureIsValid($value);
        $this->value = $value;
    }

    public static function random(): self
    {
        return new self(RamseyUuid::uuid4()->toString());
    }

    private function ensureIsValid(string $value): void
    {
        if (!RamseyUuid::isValid($value)) {
            throw new \Shared\Domain\Exceptions\ValidationException("Invalid UUID: {$value}");
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
