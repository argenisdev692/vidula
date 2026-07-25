<?php

declare(strict_types=1);

namespace Modules\VideoExport\Domain\Ports;

/**
 * Neural / AI speech denoise (background noise). Does not remove filler words —
 * those stay on Whisper + FillerCutDetector + FFmpeg cuts.
 */
interface AudioDenoisePort
{
    public function isConfigured(): bool;

    /**
     * Denoise a local PCM/WAV (or other ffmpeg-readable) audio file into $outputPath.
     */
    public function enhance(string $inputPath, string $outputPath): void;
}
