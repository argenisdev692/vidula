<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Domain\Enums;

enum ApplicationStatus: string
{
    case New = 'new';
    case Saved = 'saved';
    case Applied = 'applied';
    case Skipped = 'skipped';
    case Dismissed = 'dismissed';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
