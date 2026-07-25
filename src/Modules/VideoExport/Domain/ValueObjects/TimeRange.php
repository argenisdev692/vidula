<?php

declare(strict_types=1);

namespace Modules\VideoExport\Domain\ValueObjects;

/**
 * Half-open time interval [start, end) in seconds.
 */
final readonly class TimeRange
{
    public function __construct(
        public float $start,
        public float $end,
    ) {
        if ($this->end < $this->start) {
            throw new \InvalidArgumentException('TimeRange end must be >= start.');
        }
    }

    public function duration(): float
    {
        return $this->end - $this->start;
    }
}
