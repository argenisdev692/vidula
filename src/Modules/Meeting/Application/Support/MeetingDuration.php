<?php

declare(strict_types=1);

namespace Modules\Meeting\Application\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Single place that turns a meeting start into an end using
 * `config('meeting.duration_minutes')` (default 30).
 */
final class MeetingDuration
{
    public static function minutes(): int
    {
        $minutes = (int) config('meeting.duration_minutes', 30);

        return max(1, $minutes);
    }

    public static function endsAt(CarbonInterface|string $startsAt): CarbonImmutable
    {
        return CarbonImmutable::parse($startsAt)->addMinutes(self::minutes());
    }
}
