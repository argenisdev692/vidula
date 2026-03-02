<?php

declare(strict_types=1);

namespace Modules\Clients\Domain\ValueObjects;

/**
 * PhoneNumber Value Object
 * 
 * Property Hooks were proposed but NOT included in PHP 8.5 final release.
 * Using constructor validation instead.
 */
final readonly class PhoneNumber
{
    public string $value;

    public function __construct(string $value)
    {
        // Remove all non-numeric characters except + at the start
        $normalized = preg_replace('/[^\d+]/', '', $value);
        
        if (empty($normalized)) {
            throw new \InvalidArgumentException("Invalid phone number: {$value}");
        }
        
        $this->value = $normalized;
    }

    #[\NoDiscard]
    public function getFormatted(): string
    {
        // Simple formatting for display
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

