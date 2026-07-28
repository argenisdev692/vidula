<?php

declare(strict_types=1);

namespace Modules\Products\Domain\Enums;

/**
 * Billable catalog shape. Drives BOTH the seed markdown parser (classroom
 * indexes and video indexes use different heading conventions) and the AI
 * agent selected per topic (lesson notes vs recording script).
 */
enum ProductType: string
{
    case Classroom = 'classroom';
    case VideoTutorial = 'video_tutorial';
    case VideoPill = 'video_pill';

    public function isVideo(): bool
    {
        return $this !== self::Classroom;
    }

    /**
     * All catalog types in v1 are content-generatable (FR-1). Kept as a method
     * so future non-content products (pure services) can opt out.
     */
    public function isGeneratable(): bool
    {
        return true;
    }

    public function label(): string
    {
        return match ($this) {
            self::Classroom => 'Classroom',
            self::VideoTutorial => 'Video tutorial',
            self::VideoPill => 'Video pill',
        };
    }
}
