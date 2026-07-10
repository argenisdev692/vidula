<?php

declare(strict_types=1);

namespace Modules\Availability\Application\DTOs;

use Closure;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Create/update payload for a weekly availability rule (one slot on one weekday).
 * Store and Update share the full field set, so a single fused DTO is used.
 *
 * Times are `H:i` (24h) — the DB `time` column normalises them to `HH:MM:SS`.
 * Two guarantees beyond field validation: `end_time` must be strictly after
 * `start_time`, and an AVAILABLE slot may not overlap another available slot on
 * the same weekday (so the resolver never emits ambiguous windows).
 */
#[MapInputName(SnakeCaseMapper::class)]
final class AvailabilityRuleData extends Data
{
    public function __construct(
        public int $dayOfWeek,
        public string $startTime,
        public string $endTime,
        public bool $isAvailable = true,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'start_time' => ['required', 'date_format:H:i', self::noOverlapRule()],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'is_available' => ['required', 'boolean'],
        ];
    }

    /**
     * Rejects an available slot that overlaps another available slot on the same
     * weekday. Two intervals overlap when `existing.start < new.end` AND
     * `existing.end > new.start`. The row being edited is excluded by UUID.
     */
    private static function noOverlapRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $request = request();

            if (! $request->boolean('is_available')) {
                return; // unavailable rows never conflict
            }

            $start = (string) $value;
            $end = (string) $request->input('end_time');
            $day = $request->integer('day_of_week');

            if ($end === '' || $end <= $start) {
                return; // let date_format / after rules report malformed input
            }

            $overlaps = DB::table('availability_rules')
                ->whereNull('deleted_at')
                ->where('day_of_week', $day)
                ->where('is_available', true)
                ->when(
                    $request->route('uuid') !== null,
                    fn ($q) => $q->where('uuid', '!=', $request->route('uuid')),
                )
                ->where('start_time', '<', $end)
                ->where('end_time', '>', $start)
                ->exists();

            if ($overlaps) {
                $fail(__('This slot overlaps an existing availability window on the same day.'));
            }
        };
    }
}
