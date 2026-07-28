<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Domain\Enums;

enum StudioRunStep: string
{
    case Queued = 'queued';
    case Enriching = 'enriching';
    case Judging = 'judging';
    case AwaitingMetrics = 'awaiting_metrics';
    case Refining = 'refining';
    case Searching = 'searching';
    case Scoring = 'scoring';
    case Drafting = 'drafting';
    case Completed = 'completed';
    case Failed = 'failed';
}
