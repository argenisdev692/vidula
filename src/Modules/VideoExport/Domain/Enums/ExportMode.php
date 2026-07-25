<?php

declare(strict_types=1);

namespace Modules\VideoExport\Domain\Enums;

enum ExportMode: string
{
    case Merge = 'merge';
    case Clean = 'clean';
    case Ai = 'ai';

    public function usesSilenceCleaning(): bool
    {
        return $this === self::Clean || $this === self::Ai;
    }

    public function usesSpeechAi(): bool
    {
        return $this === self::Ai;
    }
}
