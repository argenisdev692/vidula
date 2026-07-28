<?php

declare(strict_types=1);

namespace Modules\Products\Domain\Enums;

/**
 * Where the recorded videos of a video product are published.
 */
enum VideoPlatform: string
{
    case Youtube = 'youtube';
    case Vimeo = 'vimeo';
    case Local = 'local';
    case Other = 'other';
}
