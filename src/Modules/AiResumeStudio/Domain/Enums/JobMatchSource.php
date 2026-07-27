<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Domain\Enums;

enum JobMatchSource: string
{
    case Tavily = 'tavily';
    case Firecrawl = 'firecrawl';
}
