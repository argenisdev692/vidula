<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\ValueObjects;

/**
 * OtpCode — Immutable value object representing a 6-digit OTP.
 *
 * @see https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.readonly
 */
readonly class OtpCode
{
    public function __construct(
        public string $value,
    ) {
        if (!preg_match('/^\d{6}$/', $this->value)) {
            throw new \InvalidArgumentException('OTP code must be exactly 6 digits.');
        }
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
