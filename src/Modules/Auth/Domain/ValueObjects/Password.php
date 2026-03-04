<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\ValueObjects;

/**
 * Password — Immutable password value object with PHP 8.5 property hooks.
 * 
 * Features:
 * - Automatic validation (min 8 chars, complexity)
 * - Property hooks for validation
 * - Secure hashing
 */
final readonly class Password
{
    public function __construct(
        public string $value {
            set {
                if(strlen($value) < 8) {
                    throw new \InvalidArgumentException('Password must be at least 8 characters long');
                }

                if(!preg_match('/[A-Z]/', $value)) {
                    throw new \InvalidArgumentException('Password must contain at least one uppercase letter');
                }

                if(!preg_match('/[a-z]/', $value)) {
                    throw new \InvalidArgumentException('Password must contain at least one lowercase letter');
                }

                if(!preg_match('/[0-9]/', $value)) {
                    throw new \InvalidArgumentException('Password must contain at least one number');
                }

                $this->value = $value;
            }
        },
    ) {
    }

    #[\NoDiscard]
    public static function fromPlainText(string $plainText): self
    {
        return new self($plainText);
    }

    #[\NoDiscard]
    public function hash(): string
    {
        return password_hash($this->value, PASSWORD_ARGON2ID);
    }

    #[\NoDiscard]
    public function verify(string $hashedPassword): bool
    {
        return password_verify($this->value, $hashedPassword);
    }

    public function meetsComplexityRequirements(): bool
    {
        return strlen($this->value) >= 8
            && preg_match('/[A-Z]/', $this->value)
            && preg_match('/[a-z]/', $this->value)
            && preg_match('/[0-9]/', $this->value);
    }

    #[\NoDiscard]
    public function strength(): string
    {
        $score = 0;

        if (strlen($this->value) >= 8)
            $score++;
        if (strlen($this->value) >= 12)
            $score++;
        if (preg_match('/[A-Z]/', $this->value))
            $score++;
        if (preg_match('/[a-z]/', $this->value))
            $score++;
        if (preg_match('/[0-9]/', $this->value))
            $score++;
        if (preg_match('/[^A-Za-z0-9]/', $this->value))
            $score++;

        return match (true) {
            $score >= 6 => 'strong',
            $score >= 5 => 'medium',
            default => 'weak',
        };
    }
}
