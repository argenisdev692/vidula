<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\ValueObjects;

/**
 * OtpCode — Immutable value object representing a 6-digit OTP with PHP 8.5 property hooks.
 * 
 * Features:
 * - Automatic validation (6 digits)
 * - Secure comparison with hash_equals
 * - Property hooks for validation
 * - #[\NoDiscard] attributes
 */
final readonly class OtpCode
{
    public function __construct(
        public string $value {
            set {
                if(!preg_match('/^\d{6}$/', $value)) {
                    throw new \InvalidArgumentException('OTP code must be exactly 6 digits.');
                }
                $this->value = $value;
            }
        },
    ) {
    }

    #[\NoDiscard]
    public static function generate(): self
    {
        return new self((string) random_int(100000, 999999));
    }

    #[\NoDiscard]
    public function equals(self $other): bool
    {
        return hash_equals($this->value, $other->value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
