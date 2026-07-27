<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Domain\Enums;

enum OutreachStatus: string
{
    case Draft = 'draft';
    case Edited = 'edited';
    case SentManually = 'sent_manually';
    case SentAutomated = 'sent_automated';
    case Discarded = 'discarded';
}
