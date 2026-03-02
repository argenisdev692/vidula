<?php

declare(strict_types=1);

namespace Modules\Products\Domain\Enums;

enum ProductLevel: string
{
    case Beginner = 'beginner';
    case Intermediate = 'intermediate';
    case Advanced = 'advanced';

    #[\NoDiscard]
    public function label(): string
    {
        return match($this) {
            self::Beginner => 'Beginner',
            self::Intermediate => 'Intermediate',
            self::Advanced => 'Advanced',
        };
    }

    #[\NoDiscard]
    public function order(): int
    {
        return match($this) {
            self::Beginner => 1,
            self::Intermediate => 2,
            self::Advanced => 3,
        };
    }
}
