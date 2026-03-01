<?php

declare(strict_types=1);

namespace Modules\Users\Domain\ValueObjects;

/**
 * FullName — Immutable Value Object
 *
 * Encapsulates the logic for a user's full name.
 */
final readonly class FullName
{
    public function __construct(
        public string $firstName,
        public string $lastName
    ) {
    }

    public function __toString(): string
    {
        return trim("{$this->firstName} {$this->lastName}");
    }
}
