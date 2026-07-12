<?php

declare(strict_types=1);

namespace Modules\Appointment\Domain\ValueObjects;

use Modules\Appointment\Domain\Services\SpamGuard;

/**
 * Immutable verdict produced by {@see SpamGuard}.
 * `score` is the accumulated weight, `isSpam` whether it crossed the configured
 * threshold, and `reasons` the human-readable signals that fired (e.g.
 * `keyword:casino`, `links:4`) — persisted so an operator can audit a verdict.
 */
final readonly class SpamAssessment
{
    /**
     * @param  array<int, string>  $reasons
     */
    public function __construct(
        public int $score,
        public bool $isSpam,
        public array $reasons,
    ) {}
}
