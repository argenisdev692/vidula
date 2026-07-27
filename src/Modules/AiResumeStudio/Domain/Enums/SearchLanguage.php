<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Domain\Enums;

enum SearchLanguage: string
{
    case Spanish = 'es';
    case English = 'en';
    case Both = 'both';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** Fragment appended to Tavily job-search queries. */
    public function searchFragment(): string
    {
        return match ($this) {
            self::Spanish => 'Spanish language OR español',
            self::English => 'English language',
            self::Both => 'Spanish OR English language',
        };
    }
}
