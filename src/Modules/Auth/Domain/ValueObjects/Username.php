<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\ValueObjects;

/**
 * Username — Immutable username value object with PHP 8.5 property hooks.
 * 
 * Features:
 * - Automatic validation (alphanumeric, underscore, hyphen)
 * - Normalization (lowercase, trim)
 * - Property hooks for validation
 */
final readonly class Username
{
    public function __construct(
        public string $value {
            get => strtolower($this->value);
            set {
                $normalized = strtolower(trim($value));
                
                if (strlen($normalized) < 3) {
                    throw new \InvalidArgumentException('Username must be at least 3 characters long');
                }
                
                if (strlen($normalized) > 30) {
                    throw new \InvalidArgumentException('Username must not exceed 30 characters');
                }
                
                if (!preg_match('/^[a-z0-9_-]+$/', $normalized)) {
                    throw new \InvalidArgumentException(
                        'Username can only contain lowercase letters, numbers, underscores, and hyphens'
                    );
                }
                
                if (preg_match('/^[0-9]/', $normalized)) {
                    throw new \InvalidArgumentException('Username cannot start with a number');
                }
                
                $this->value = $normalized;
            }
        }
    ) {}

    #[\NoDiscard]
    public static function fromEmail(string $email): self
    {
        $username = strstr($email, '@', true);
        $normalized = preg_replace('/[^a-z0-9_-]/', '_', strtolower($username));
        
        return new self($normalized);
    }

    #[\NoDiscard]
    public static function generate(string $baseName, int $suffix = 0): self
    {
        $normalized = preg_replace('/[^a-z0-9_-]/', '_', strtolower($baseName));
        $username = $suffix > 0 ? "{$normalized}_{$suffix}" : $normalized;
        
        return new self($username);
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
