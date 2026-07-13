<?php

declare(strict_types=1);

namespace Modules\SocialMedia\Domain\Enums;

/**
 * Output language the user picks for AI generation — never inferred, always
 * explicit (the same topic/niche can be worked in either market).
 */
enum ContentLanguage: string
{
    case Spanish = 'es';
    case English = 'en';

    public function label(): string
    {
        return match ($this) {
            self::Spanish => 'Español (LatAm neutro)',
            self::English => 'English',
        };
    }
}
