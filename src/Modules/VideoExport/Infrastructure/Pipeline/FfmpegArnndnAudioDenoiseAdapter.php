<?php

declare(strict_types=1);

namespace Modules\VideoExport\Infrastructure\Pipeline;

use Modules\VideoExport\Domain\Ports\AudioDenoisePort;
use RuntimeException;

/**
 * Local AI denoise via FFmpeg {@see https://ffmpeg.org/ffmpeg-filters.html#arnndn}.
 * Requires a .rnnn model file (e.g. from richardpl/arnndn-models).
 */
final readonly class FfmpegArnndnAudioDenoiseAdapter implements AudioDenoisePort
{
    public function __construct(
        private FfmpegBinaryRunner $ffmpeg,
        private string $modelPath,
        private float $mix = 0.8,
    ) {}

    public function isConfigured(): bool
    {
        return $this->modelPath !== '' && is_readable($this->modelPath);
    }

    public function enhance(string $inputPath, string $outputPath): void
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException(
                'AI denoise (arnndn) is not configured. Set VIDEO_EXPORT_ARNNDN_MODEL to a readable .rnnn file.',
            );
        }

        $model = $this->escapeFilterPath($this->modelPath);
        $mix = max(0.0, min(1.0, $this->mix));
        $filter = sprintf('arnndn=m=%s:mix=%s', $model, number_format($mix, 2, '.', ''));

        $this->ffmpeg->applyAudioFilter($inputPath, $outputPath, $filter);
    }

    private function escapeFilterPath(string $path): string
    {
        return str_replace(
            ['\\', ':', "'", '[', ']'],
            ['\\\\', '\\:', "\\'", '\\[', '\\]'],
            $path,
        );
    }
}
