<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Domain\Enums;

enum StudioMode: string
{
    case Career = 'career';
    case Other = 'other';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
