<?php

declare(strict_types=1);

namespace Shared\Domain\ValueObjects;

abstract readonly class StringValueObject
{
    public function __construct(
        public string $value
    ) {
        $this->validate($value);
    }

    protected function validate(string $value): void
    {
    }


    public static function fromString(string $value): static
    {
        return new static($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
