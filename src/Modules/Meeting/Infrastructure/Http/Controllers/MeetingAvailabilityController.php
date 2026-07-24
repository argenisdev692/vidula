<?php

declare(strict_types=1);

namespace Modules\Meeting\Infrastructure\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Availability\Domain\Services\AvailabilityResolver;
use Modules\Availability\Domain\ValueObjects\ResolvedDay;

/**
 * Effective availability for the meeting form (open days + time windows).
 * Scoped to CREATE_MEETINGS|UPDATE_MEETINGS so staff scheduling meetings do not
 * need VIEW_ANY_AVAILABILITY_RULES. Range capped at 92 days (OWASP).
 */
final readonly class MeetingAvailabilityController
{
    private const int MAX_DAYS = 92;

    public function __invoke(Request $request, AvailabilityResolver $resolver): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $from = CarbonImmutable::parse($validated['from'] ?? CarbonImmutable::now()->startOfMonth()->format('Y-m-d'));
        $to = CarbonImmutable::parse($validated['to'] ?? $from->endOfMonth()->format('Y-m-d'));

        if ($from->diffInDays($to) > self::MAX_DAYS) {
            $to = $from->addDays(self::MAX_DAYS);
        }

        $days = array_map(
            static fn (ResolvedDay $day): array => $day->toArray(),
            array_values($resolver->resolveRange($from, $to)),
        );

        return response()->json([
            'data' => $days,
            'meta' => [
                'duration_minutes' => (int) config('meeting.duration_minutes', 30),
            ],
        ]);
    }
}
