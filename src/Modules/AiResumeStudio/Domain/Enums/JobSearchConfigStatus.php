<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Domain\Enums;

enum JobSearchConfigStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
}
