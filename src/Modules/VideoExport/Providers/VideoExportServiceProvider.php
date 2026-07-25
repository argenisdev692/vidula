<?php

declare(strict_types=1);

namespace Modules\VideoExport\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\VideoExport\Domain\Ports\AudioDenoisePort;
use Modules\VideoExport\Domain\Services\CutPlanner;
use Modules\VideoExport\Domain\Services\SilenceCutParser;
use Modules\VideoExport\Infrastructure\Pipeline\AudioEnhanceChain;
use Modules\VideoExport\Infrastructure\Pipeline\FfmpegArnndnAudioDenoiseAdapter;
use Modules\VideoExport\Infrastructure\Pipeline\FfmpegBinaryRunner;
use Modules\VideoExport\Infrastructure\Pipeline\HttpAudioDenoiseAdapter;
use Modules\VideoExport\Infrastructure\Pipeline\InputResolver;
use Modules\VideoExport\Infrastructure\Pipeline\OpenAiWhisperTranscriber;
use Modules\VideoExport\Infrastructure\Pipeline\ScriptReviewService;
use Modules\VideoExport\Infrastructure\Pipeline\VideoExportPipeline;
use Modules\VideoExport\Infrastructure\Pipeline\VideoWorkspace;

/**
 * Video export module (no DB tables). Requires ffmpeg/ffprobe in the Sail image
 * and Horizon worker on the `video-export` queue.
 *
 * FFMpeg facade is registered by pbmedia/laravel-ffmpeg Package Discovery.
 */
final class VideoExportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(base_path('config/video-export.php'), 'video-export');

        $this->app->singleton(FfmpegBinaryRunner::class, static fn (): FfmpegBinaryRunner => FfmpegBinaryRunner::fromConfig());
        $this->app->singleton(CutPlanner::class, static fn (): CutPlanner => new CutPlanner(
            (float) config('video-export.min_segment_seconds', 0.25),
        ));
        $this->app->singleton(SilenceCutParser::class);
        $this->app->singleton(VideoWorkspace::class);
        $this->app->singleton(AudioEnhanceChain::class);
        $this->app->singleton(OpenAiWhisperTranscriber::class);
        $this->app->singleton(InputResolver::class);
        $this->app->singleton(ScriptReviewService::class);

        $this->app->singleton(AudioDenoisePort::class, function ($app): AudioDenoisePort {
            $driver = (string) config('video-export.ai_denoise.driver', 'arnndn');

            if ($driver === 'http') {
                return new HttpAudioDenoiseAdapter(
                    endpointUrl: (string) config('video-export.ai_denoise.http_url', ''),
                    token: (string) config('video-export.ai_denoise.http_token', ''),
                    timeoutSeconds: (int) config('video-export.ai_denoise.http_timeout', 600),
                );
            }

            return new FfmpegArnndnAudioDenoiseAdapter(
                ffmpeg: $app->make(FfmpegBinaryRunner::class),
                modelPath: (string) config('video-export.ai_denoise.arnndn_model', ''),
                mix: (float) config('video-export.ai_denoise.arnndn_mix', 0.8),
            );
        });

        $this->app->singleton(VideoExportPipeline::class);
    }

    public function boot(): void
    {
        Route::middleware('web')->group(__DIR__.'/../Infrastructure/Routes/web.php');
        Route::middleware('api')->prefix('api')->group(__DIR__.'/../Infrastructure/Routes/api.php');
    }
}
