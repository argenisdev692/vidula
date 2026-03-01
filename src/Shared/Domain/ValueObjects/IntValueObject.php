<?php

declare(strict_types=1);

namespace Shared\Domain\ValueObjects;

abstract readonly class IntValueObject
{
    public function __construct(
        public int $value
    ) {
    }

    public static function fromInt(int $value): static
    {
        return new static($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}

