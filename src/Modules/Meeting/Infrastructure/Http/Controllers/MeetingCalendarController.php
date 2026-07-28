<?php

declare(strict_types=1);

namespace Modules\Meeting\Infrastructure\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Meeting\Application\Queries\GetMeetingCalendarFeedHandler;

/**
 * Read-only combined calendar feed — own Meetings + the read-only Appointment
 * overlay (plan.md §3). `start`/`end` match FullCalendar's default JSON-feed
 * GET param names (confirmed via context7 — no custom `startParam`/`endParam`
 * needed on the frontend). Range is capped at 92 days, mirroring
 * `AvailabilityCalendarController::MAX_DAYS` (OWASP — unrestricted resource
 * consumption).
 */
final readonly class MeetingCalendarController
{
    public function __invoke(Request $request, GetMeetingCalendarFeedHandler $feed): JsonResponse
    {
        $validated = $request->validate([
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date', 'after_or_equal:start'],
        ]);

        $from = CarbonImmutable::parse($validated['start'] ?? CarbonImmutable::now()->startOfMonth());
        $to = CarbonImmutable::parse($validated['end'] ?? $from->endOfMonth());
        $maxDays = max(1, (int) config('meeting.calendar_max_days', 92));

        if ($from->diffInDays($to) > $maxDays) {
            $to = $from->addDays($maxDays);
        }

        return response()->json(['data' => $feed->handle($from, $to)->values()]);
    }
}
