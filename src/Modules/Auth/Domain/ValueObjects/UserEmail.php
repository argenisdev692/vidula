<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\ValueObjects;

use Shared\Domain\ValueObjects\StringValueObject;

final readonly class UserEmail extends StringValueObject
{
    public function __construct(string $value)
    {
        parent::__construct(strtolower(trim($value)));
    }


    protected function validate(string $value): void
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email address: {$value}");
        }
    }

    public function domain(): string
    {
        return substr($this->value, strpos($this->value, '@') + 1);
    }
}

