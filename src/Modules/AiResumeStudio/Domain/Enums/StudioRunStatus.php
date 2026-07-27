<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Domain\Enums;

enum StudioRunStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
}
