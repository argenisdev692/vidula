<?php

declare(strict_types=1);

namespace Modules\Products\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class Money
{
    public function __construct(
        public float $amount {
            set {
                if($value < 0) {
                    throw new InvalidArgumentException(
                    "Price cannot be negative, got: {$value}"
                    );
                }
                $this->amount = $value;
            }
        },
        public string $currency {
            set {
                if(strlen($value) !== 3) {
                    throw new InvalidArgumentException(
                    "Currency must be 3 characters (ISO 4217), got: {$value}"
                    );
                }
                $this->currency = strtoupper($value);
            }
        }
    ) {
    }

    #[\NoDiscard]
    public function add(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                "Cannot add different currencies: {$this->currency} and {$other->currency}"
            );
        }
        return new self($this->amount + $other->amount, $this->currency);
    }

    #[\NoDiscard]
    public function subtract(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                "Cannot subtract different currencies: {$this->currency} and {$other->currency}"
            );
        }
        return new self($this->amount - $other->amount, $this->currency);
    }

    #[\NoDiscard]
    public function multiply(float $factor): self
    {
        return new self($this->amount * $factor, $this->currency);
    }

    #[\NoDiscard]
    public function divide(float $divisor): self
    {
        if ($divisor == 0) {
            throw new InvalidArgumentException('Cannot divide by zero');
        }
        return new self($this->amount / $divisor, $this->currency);
    }

    #[\NoDiscard]
    public function format(): string
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }

    #[\NoDiscard]
    public function isZero(): bool
    {
        return $this->amount === 0.0;
    }

    #[\NoDiscard]
    public function isPositive(): bool
    {
        return $this->amount > 0;
    }

    #[\NoDiscard]
    public function equals(self $other): bool
    {
        return $this->amount === $other->amount
            && $this->currency === $other->currency;
    }
}
