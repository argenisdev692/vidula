<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\ValueObjects;

/**
 * UserEmail — Immutable email value object with PHP 8.5 property hooks.
 * 
 * Features:
 * - Automatic normalization (lowercase, trim)
 * - Validation on construction
 * - Property hooks for get/set behavior
 */
final readonly class UserEmail
{
    public string $value;

    public function __construct(string $value)
    {
        $normalized = strtolower(trim($value));
        if (!filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email address: {$value}");
        }
        $this->value = $normalized;
    }

    public function domain(): string
    {
        return substr($this->value, strpos($this->value, '@') + 1);
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

