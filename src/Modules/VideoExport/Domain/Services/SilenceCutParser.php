<?php

declare(strict_types=1);

namespace Modules\VideoExport\Domain\Services;

use Modules\VideoExport\Domain\ValueObjects\TimeRange;

/** Parses ffmpeg silencedetect stderr into cut ranges. */
final readonly class SilenceCutParser
{
    /**
     * @return list<TimeRange>
     */
    public function parse(string $stderr, float $durationSeconds): array
    {
        $cuts = [];
        $openStart = null;

        foreach (preg_split('/\R/', $stderr) ?: [] as $line) {
            if (preg_match('/silence_start:\s*(-?\d+(?:\.\d+)?)/', $line, $startMatch) === 1) {
                $openStart = max(0.0, (float) $startMatch[1]);

                continue;
            }
            if (
                $openStart !== null
                && preg_match('/silence_end:\s*(-?\d+(?:\.\d+)?)/', $line, $endMatch) === 1
            ) {
                $end = (float) $endMatch[1];
                if ($end > $openStart) {
                    $cuts[] = new TimeRange($openStart, $end);
                }
                $openStart = null;
            }
        }

        if ($openStart !== null && $durationSeconds > $openStart) {
            $cuts[] = new TimeRange($openStart, $durationSeconds);
        }

        return $cuts;
    }
}
