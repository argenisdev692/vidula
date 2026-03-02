<?php

declare(strict_types=1);

namespace Modules\Clients\Domain\ValueObjects;

/**
 * Email Value Object
 * 
 * Property Hooks were proposed but NOT included in PHP 8.5 final release.
 * Using constructor validation instead.
 */
final readonly class Email
{
    public string $value;

    public function __construct(string $value)
    {
        $normalized = strtolower(trim($value));
        
        if (!filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email format: {$value}");
        }
        
        $this->value = $normalized;
    }

    #[\NoDiscard]
    public function getDomain(): string
    {
        return explode('@', $this->value)[1] ?? '';
    }

    #[\NoDiscard]
    public function getLocalPart(): string
    {
        return explode('@', $this->value)[0] ?? '';
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

