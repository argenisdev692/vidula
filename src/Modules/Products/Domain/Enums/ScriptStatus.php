<?php

declare(strict_types=1);

namespace Modules\Products\Domain\Enums;

/**
 * Review state of one topic's script / lesson notes. `NeedsReview` is the
 * graceful-degradation landing state: research or generation failed for that
 * single topic, the run continued, and a human must look at it (spec FR-15).
 */
enum ScriptStatus: string
{
    case Draft = 'draft';
    case Generated = 'generated';
    case Verified = 'verified';
    case NeedsReview = 'needs_review';
    case Recorded = 'recorded';

    /**
     * Verified/recorded work is human-owned — a re-generation must never
     * silently overwrite it.
     */
    public function isHumanApproved(): bool
    {
        return $this === self::Verified || $this === self::Recorded;
    }
}
