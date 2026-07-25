<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\VideoExport;

use Modules\VideoExport\Domain\Enums\AudioEnhanceMode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AudioEnhanceModeTest extends TestCase
{
    #[Test]
    #[DataProvider('resolveCases')]
    public function it_resolves_mode_from_legacy_boolean_and_string(
        bool $enabled,
        string $mode,
        AudioEnhanceMode $expected,
    ): void {
        $this->assertSame($expected, AudioEnhanceMode::resolve($enabled, $mode));
    }

    /**
     * @return array<string, array{0: bool, 1: string, 2: AudioEnhanceMode}>
     */
    public static function resolveCases(): array
    {
        return [
            'legacy off wins' => [false, 'dsp', AudioEnhanceMode::Off],
            'legacy off with ai' => [false, 'ai', AudioEnhanceMode::Off],
            'dsp default' => [true, 'dsp', AudioEnhanceMode::Dsp],
            'ai mode' => [true, 'ai', AudioEnhanceMode::Ai],
            'explicit off' => [true, 'off', AudioEnhanceMode::Off],
            'unknown falls back to dsp' => [true, 'nope', AudioEnhanceMode::Dsp],
        ];
    }

    #[Test]
    public function it_exposes_capability_helpers(): void
    {
        $this->assertFalse(AudioEnhanceMode::Off->isEnabled());
        $this->assertTrue(AudioEnhanceMode::Dsp->usesDsp());
        $this->assertTrue(AudioEnhanceMode::Ai->usesAiDenoise());
        $this->assertFalse(AudioEnhanceMode::Dsp->usesAiDenoise());
    }
}
