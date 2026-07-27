<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Domain\Enums;

enum OutreachKind: string
{
    case Cover = 'cover';
    case Digest = 'digest';
}
