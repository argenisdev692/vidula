<?php

declare(strict_types=1);

namespace Modules\Products\Domain\Enums;

enum ProductType: string
{
    case Classroom = 'classroom';
    case Video = 'video';

    #[\NoDiscard]
    public function label(): string
    {
        return match($this) {
            self::Classroom => 'Classroom',
            self::Video => 'Video',
        };
    }

    #[\NoDiscard]
    public function isClassroom(): bool
    {
        return $this === self::Classroom;
    }

    #[\NoDiscard]
    public function isVideo(): bool
    {
        return $this === self::Video;
    }
}
