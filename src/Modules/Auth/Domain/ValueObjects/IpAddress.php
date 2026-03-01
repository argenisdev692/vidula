<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\ValueObjects;

/**
 * IpAddress — Validated IP address value object (IPv4/IPv6).
 */
readonly class IpAddress
{
    public function __construct(
        public string $value,
    ) {
        if (!filter_var($this->value, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException("Invalid IP address: {$this->value}");
        }
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
