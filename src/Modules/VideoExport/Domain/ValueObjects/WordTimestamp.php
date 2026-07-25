<?php

declare(strict_types=1);

namespace Modules\VideoExport\Domain\ValueObjects;

final readonly class WordTimestamp
{
    public function __construct(
        public string $text,
        public float $start,
        public float $end,
    ) {}
}
