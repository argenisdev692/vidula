<?php

declare(strict_types=1);

namespace Modules\VideoExport\Domain\Services;

use Modules\VideoExport\Domain\ValueObjects\TimeRange;

/**
 * Turns the union of cut ranges into complementary KEEP ranges for FFmpeg trim.
 * Ported from Nest CutPlannerService.
 */
final readonly class CutPlanner
{
    public function __construct(
        private float $minSegmentSeconds = 0.25,
    ) {}

    /**
     * @param  list<TimeRange>  $cuts
     * @return list<TimeRange>
     */
    public function invertCuts(array $cuts, float $durationSeconds): array
    {
        $normalized = [];
        foreach ($cuts as $cut) {
            $start = max(0.0, $cut->start);
            $end = min($durationSeconds, $cut->end);
            if ($end - $start >= $this->minSegmentSeconds) {
                $normalized[] = new TimeRange($start, $end);
            }
        }

        usort($normalized, static fn (TimeRange $a, TimeRange $b): int => $a->start <=> $b->start);

        $keep = [];
        $cursor = 0.0;
        foreach ($normalized as $cut) {
            if ($cut->start - $cursor >= $this->minSegmentSeconds) {
                $keep[] = new TimeRange($cursor, $cut->start);
            }
            $cursor = max($cursor, $cut->end);
        }

        if ($durationSeconds - $cursor >= $this->minSegmentSeconds) {
            $keep[] = new TimeRange($cursor, $durationSeconds);
        }

        if ($keep === []) {
            return [new TimeRange(0.0, $durationSeconds)];
        }

        return $keep;
    }

    /**
     * @param  list<TimeRange>  $keep
     */
    public function totalDuration(array $keep): float
    {
        $total = 0.0;
        foreach ($keep as $range) {
            $total += $range->duration();
        }

        return round($total * 1000) / 1000;
    }
}
