<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\ValueObjects;

/**
 * IpAddress — Validated IP address value object with PHP 8.5 property hooks.
 * 
 * Features:
 * - Automatic validation on construction
 * - Support for IPv4 and IPv6
 * - Property hooks for validation
 */
final readonly class IpAddress
{
    public function __construct(
        public string $value {
            set {
                try {
                    $ip = filter_var($value, FILTER_VALIDATE_IP, FILTER_THROW_ON_FAILURE);
                    $this->value = $ip;
                } catch(\ValueError $e) {
                    throw new \InvalidArgumentException("Invalid IP address: {$value}", previous: $e);
                }
            }
        },
    ) {
    }

    public function isIPv4(): bool
    {
        return filter_var($this->value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    public function isIPv6(): bool
    {
        return filter_var($this->value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
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
