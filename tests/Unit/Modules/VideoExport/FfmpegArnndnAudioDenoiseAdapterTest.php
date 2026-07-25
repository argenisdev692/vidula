<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\VideoExport;

use Modules\VideoExport\Infrastructure\Pipeline\AudioEnhanceChain;
use Modules\VideoExport\Infrastructure\Pipeline\FfmpegArnndnAudioDenoiseAdapter;
use Modules\VideoExport\Infrastructure\Pipeline\FfmpegBinaryRunner;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FfmpegArnndnAudioDenoiseAdapterTest extends TestCase
{
    #[Test]
    public function it_is_not_configured_without_readable_model(): void
    {
        $adapter = new FfmpegArnndnAudioDenoiseAdapter(
            ffmpeg: new FfmpegBinaryRunner('ffmpeg', 'ffprobe'),
            modelPath: '',
        );

        $this->assertFalse($adapter->isConfigured());
    }

    #[Test]
    public function it_is_configured_when_model_file_exists(): void
    {
        $model = tempnam(sys_get_temp_dir(), 'rnnn-');
        $this->assertNotFalse($model);
        file_put_contents($model, 'fake-model');

        try {
            $adapter = new FfmpegArnndnAudioDenoiseAdapter(
                ffmpeg: new FfmpegBinaryRunner('ffmpeg', 'ffprobe'),
                modelPath: $model,
            );
            $this->assertTrue($adapter->isConfigured());
        } finally {
            @unlink($model);
        }
    }

    #[Test]
    public function post_ai_chain_is_loudness_only(): void
    {
        $chain = (new AudioEnhanceChain)->buildPostAi();

        $this->assertStringContainsString('loudnorm', $chain);
        $this->assertStringNotContainsString('afftdn', $chain);
    }
}
