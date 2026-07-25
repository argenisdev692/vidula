<?php

declare(strict_types=1);

namespace Modules\VideoExport\Domain\Enums;

enum AudioEnhanceMode: string
{
    case Off = 'off';
    case Dsp = 'dsp';
    case Ai = 'ai';

    public function isEnabled(): bool
    {
        return $this !== self::Off;
    }

    public function usesDsp(): bool
    {
        return $this === self::Dsp;
    }

    public function usesAiDenoise(): bool
    {
        return $this === self::Ai;
    }

    /**
     * Resolve from API fields. Boolean false always wins (legacy toggle off).
     */
    public static function resolve(bool $audioEnhancementEnabled, string $mode): self
    {
        if (! $audioEnhancementEnabled) {
            return self::Off;
        }

        return self::tryFrom($mode) ?? self::Dsp;
    }
}
