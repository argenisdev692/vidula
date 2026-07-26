<?php

declare(strict_types=1);

namespace Modules\VideoExport\Infrastructure\Pipeline;

use Modules\VideoExport\Domain\ValueObjects\TimeRange;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Nest-parity ffmpeg/ffprobe runner via Symfony Process (no shell).
 *
 * Low-memory mode: ultrafast preset, 1 encode/filter thread, and pairwise
 * concat (never open all sources in one filter_complex) to avoid SIGKILL/OOM.
 */
final readonly class FfmpegBinaryRunner
{
    public function __construct(
        private string $ffmpegBin = '',
        private string $ffprobeBin = '',
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            ffmpegBin: (string) config('laravel-ffmpeg.ffmpeg.binaries', 'ffmpeg'),
            ffprobeBin: (string) config('laravel-ffmpeg.ffprobe.binaries', 'ffprobe'),
        );
    }

    /**
     * @param  list<string>  $args
     */
    public function run(array $args, string $label = 'ffmpeg'): string
    {
        $process = new Process([$this->ffmpegBin, ...$args]);
        $process->setTimeout((float) config('laravel-ffmpeg.timeout', 3600));
        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        // silencedetect writes to stderr even on success
        return $process->getErrorOutput().$process->getOutput();
    }

    /**
     * @param  list<string>  $args
     */
    public function probe(array $args): string
    {
        $process = new Process([$this->ffprobeBin, ...$args]);
        $process->setTimeout(120);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }

    public function probeDurationSeconds(string $path): float
    {
        $out = $this->probe([
            '-v', 'quiet',
            '-show_entries', 'format=duration',
            '-of', 'default=noprint_wrappers=1:nokey=1',
            $path,
        ]);
        $value = (float) trim($out);

        return is_finite($value) ? $value : 0.0;
    }

    /**
     * @param  list<string>  $inputs
     */
    public function mergeAndRender(array $inputs, string $outPath, bool $lowMemory = false): void
    {
        if ($lowMemory && count($inputs) > 2) {
            $this->mergePairwise($inputs, $outPath, finalHd: true, lowMemory: true);

            return;
        }

        $render = config('video-export.render');
        $args = ['-y', ...$this->threadFlags($lowMemory)];

        if (count($inputs) === 1) {
            $args = [
                ...$args,
                '-i', array_first($inputs),
                '-vf', $this->scalePadFilter(),
                '-af', sprintf(
                    'aformat=sample_rates=%d:channel_layouts=stereo',
                    (int) $render['audio_sample_rate'],
                ),
                ...$this->hdEncodeFlags($lowMemory),
                $outPath,
            ];
        } else {
            $filterParts = [];
            $concatLabels = [];
            foreach ($inputs as $i => $_) {
                $filterParts[] = sprintf('[%d:v]%s[v%d]', $i, $this->scalePadFilter(), $i);
                $filterParts[] = sprintf(
                    '[%d:a]aformat=sample_rates=%d:channel_layouts=stereo[a%d]',
                    $i,
                    (int) $render['audio_sample_rate'],
                    $i,
                );
                $concatLabels[] = sprintf('[v%d][a%d]', $i, $i);
            }
            $filterParts[] = sprintf(
                '%sconcat=n=%d:v=1:a=1[outv][outa]',
                implode('', $concatLabels),
                count($inputs),
            );
            foreach ($inputs as $input) {
                $args[] = '-i';
                $args[] = $input;
            }
            $args = [
                ...$args,
                '-filter_complex', implode(';', $filterParts),
                '-map', '[outv]',
                '-map', '[outa]',
                ...$this->hdEncodeFlags($lowMemory),
                $outPath,
            ];
        }

        $this->run($args, 'merge and render');
    }

    /**
     * @param  list<string>  $inputs
     */
    public function mergeVideos(array $inputs, string $outPath, bool $lowMemory = false): void
    {
        if ($lowMemory && count($inputs) > 2) {
            $this->mergePairwise($inputs, $outPath, finalHd: false, lowMemory: true);

            return;
        }

        $render = config('video-export.render');
        $merge = config('video-export.merge_intermediate');
        $filterParts = [];
        $concatLabels = [];

        foreach ($inputs as $i => $_) {
            $filterParts[] = sprintf('[%d:v]%s[v%d]', $i, $this->scalePadFilter(), $i);
            $filterParts[] = sprintf(
                '[%d:a]aformat=sample_rates=%d:channel_layouts=stereo[a%d]',
                $i,
                (int) $render['audio_sample_rate'],
                $i,
            );
            $concatLabels[] = sprintf('[v%d][a%d]', $i, $i);
        }
        $filterParts[] = sprintf(
            '%sconcat=n=%d:v=1:a=1[outv][outa]',
            implode('', $concatLabels),
            count($inputs),
        );

        $args = ['-y', ...$this->threadFlags($lowMemory)];
        foreach ($inputs as $input) {
            $args[] = '-i';
            $args[] = $input;
        }

        $preset = $lowMemory
            ? (string) config('video-export.low_memory.preset', 'ultrafast')
            : (string) $merge['preset'];

        $args = [
            ...$args,
            '-filter_complex', implode(';', $filterParts),
            '-map', '[outv]',
            '-map', '[outa]',
            '-c:v', 'libx264',
            '-preset', $preset,
            '-crf', (string) $merge['crf'],
            '-pix_fmt', (string) $render['pixel_format'],
            '-c:a', 'aac',
            '-b:a', (string) $merge['audio_bitrate'],
            '-movflags', '+faststart',
            $outPath,
        ];

        $this->run($args, 'merge videos');
    }

    /**
     * @param  list<TimeRange>  $keepRanges
     */
    public function render(
        string $videoPath,
        array $keepRanges,
        string $outPath,
        ?string $audioDspChain = null,
        bool $lowMemory = false,
    ): void {
        $render = config('video-export.render');
        $filterParts = [];
        $concatLabels = [];

        foreach ($keepRanges as $i => $range) {
            $filterParts[] = sprintf(
                '[0:v]trim=start=%s:end=%s,setpts=PTS-STARTPTS[v%d]',
                $range->start,
                $range->end,
                $i,
            );
            $filterParts[] = sprintf(
                '[0:a]atrim=start=%s:end=%s,asetpts=PTS-STARTPTS[a%d]',
                $range->start,
                $range->end,
                $i,
            );
            $concatLabels[] = sprintf('[v%d][a%d]', $i, $i);
        }

        $filterParts[] = sprintf(
            '%sconcat=n=%d:v=1:a=1[cv][ca]',
            implode('', $concatLabels),
            count($keepRanges),
        );
        $filterParts[] = sprintf('[cv]%s[outv]', $this->scalePadFilter());
        $audioFormat = sprintf(
            'aformat=sample_rates=%d:channel_layouts=stereo',
            (int) $render['audio_sample_rate'],
        );
        $filterParts[] = $audioDspChain !== null && $audioDspChain !== ''
            ? sprintf('[ca]%s,%s[outa]', $audioDspChain, $audioFormat)
            : sprintf('[ca]%s[outa]', $audioFormat);

        $this->run([
            '-y',
            ...$this->threadFlags($lowMemory),
            '-i', $videoPath,
            '-filter_complex', implode(';', $filterParts),
            '-map', '[outv]',
            '-map', '[outa]',
            ...$this->hdEncodeFlags($lowMemory),
            $outPath,
        ], 'render export');
    }

    public function extractWhisperAudio(string $videoPath, string $outPath): void
    {
        $w = config('video-export.whisper');
        $this->run([
            '-y',
            '-i', $videoPath,
            '-vn',
            '-ac', (string) $w['channels'],
            '-ar', (string) $w['sample_rate'],
            '-b:a', (string) $w['bitrate'],
            $outPath,
        ], 'extract whisper audio');
    }

    /** Extract PCM WAV for AI denoise (48 kHz stereo). */
    public function extractWav(string $videoPath, string $outPath): void
    {
        $rate = (int) config('video-export.render.audio_sample_rate', 48000);
        $channels = (int) config('video-export.render.audio_channels', 2);

        $this->run([
            '-y',
            '-i', $videoPath,
            '-vn',
            '-acodec', 'pcm_s16le',
            '-ar', (string) $rate,
            '-ac', (string) $channels,
            $outPath,
        ], 'extract wav');
    }

    public function applyAudioFilter(string $inputPath, string $outPath, string $audioFilter): void
    {
        $this->run([
            '-y',
            '-i', $inputPath,
            '-af', $audioFilter,
            $outPath,
        ], 'apply audio filter');
    }

    /**
     * Replace the video's audio track; optional filter on the new audio (e.g. loudnorm).
     */
    public function replaceAudioTrack(
        string $videoPath,
        string $audioPath,
        string $outPath,
        ?string $audioFilter = null,
    ): void {
        $r = config('video-export.render');

        if ($audioFilter !== null && $audioFilter !== '') {
            $this->run([
                '-y',
                '-i', $videoPath,
                '-i', $audioPath,
                '-filter_complex', sprintf('[1:a]%s[outa]', $audioFilter),
                '-map', '0:v:0',
                '-map', '[outa]',
                '-c:v', 'copy',
                '-c:a', (string) $r['audio_codec'],
                '-b:a', (string) $r['audio_bitrate'],
                '-ar', (string) $r['audio_sample_rate'],
                '-ac', (string) $r['audio_channels'],
                '-movflags', '+faststart',
                $outPath,
            ], 'replace audio');

            return;
        }

        $this->run([
            '-y',
            '-i', $videoPath,
            '-i', $audioPath,
            '-map', '0:v:0',
            '-map', '1:a:0',
            '-c:v', 'copy',
            '-c:a', (string) $r['audio_codec'],
            '-b:a', (string) $r['audio_bitrate'],
            '-ar', (string) $r['audio_sample_rate'],
            '-ac', (string) $r['audio_channels'],
            '-movflags', '+faststart',
            $outPath,
        ], 'replace audio');
    }

    public function detectSilenceStderr(string $videoPath, int $thresholdSeconds): string
    {
        $noise = (string) config('video-export.silence_noise_db', '-30dB');

        return $this->run([
            '-hide_banner',
            '-nostats',
            '-i', $videoPath,
            '-af', sprintf('silencedetect=noise=%s:d=%d', $noise, $thresholdSeconds),
            '-f', 'null',
            '-',
        ], 'detect silence');
    }

    /**
     * Concat two-at-a-time into temps so peak RAM stays near a 2-input encode.
     *
     * @param  list<string>  $inputs
     */
    private function mergePairwise(array $inputs, string $outPath, bool $finalHd, bool $lowMemory): void
    {
        $dir = dirname($outPath);
        $current = array_first($inputs);
        $temps = [];

        try {
            foreach (array_slice($inputs, 1) as $index => $next) {
                $isLast = $index === count($inputs) - 2;
                $target = ($isLast && $finalHd)
                    ? $outPath
                    : $dir.DIRECTORY_SEPARATOR.sprintf('pair_%03d.mp4', $index);

                if ($finalHd && $isLast) {
                    $this->mergeAndRender([$current, $next], $target, $lowMemory);
                } else {
                    $this->mergeVideos([$current, $next], $target, $lowMemory);
                }

                if ($current !== array_first($inputs) && is_file($current)) {
                    @unlink($current);
                }

                $current = $target;
                if ($target !== $outPath) {
                    $temps[] = $target;
                }
            }

            if ($current !== $outPath && is_file($current)) {
                if (! @rename($current, $outPath) && ! @copy($current, $outPath)) {
                    throw new \RuntimeException('Failed to finalize pairwise merge output.');
                }
                @unlink($current);
            }
        } finally {
            foreach ($temps as $temp) {
                if ($temp !== $outPath && is_file($temp)) {
                    @unlink($temp);
                }
            }
        }
    }

    private function scalePadFilter(): string
    {
        $w = (int) config('video-export.render.width');
        $h = (int) config('video-export.render.height');
        $fps = (int) config('video-export.render.fps');

        return sprintf(
            'scale=%d:%d:force_original_aspect_ratio=decrease,pad=%d:%d:(ow-iw)/2:(oh-ih)/2,fps=%d,setsar=1',
            $w,
            $h,
            $w,
            $h,
            $fps,
        );
    }

    /**
     * @return list<string>
     */
    private function threadFlags(bool $lowMemory): array
    {
        if (! $lowMemory) {
            return [];
        }

        $threads = max(1, (int) config('video-export.low_memory.threads', 1));
        $filterThreads = max(1, (int) config('video-export.low_memory.filter_threads', 1));

        return [
            '-threads', (string) $threads,
            '-filter_threads', (string) $filterThreads,
        ];
    }

    /**
     * @return list<string>
     */
    private function hdEncodeFlags(bool $lowMemory = false): array
    {
        $r = config('video-export.render');
        $preset = $lowMemory
            ? (string) config('video-export.low_memory.preset', 'ultrafast')
            : (string) $r['preset'];

        return [
            '-c:v', (string) $r['video_codec'],
            '-preset', $preset,
            '-b:v', (string) $r['video_bitrate'],
            '-maxrate', (string) $r['video_maxrate'],
            '-bufsize', (string) $r['video_bufsize'],
            '-pix_fmt', (string) $r['pixel_format'],
            '-r', (string) $r['fps'],
            '-c:a', (string) $r['audio_codec'],
            '-b:a', (string) $r['audio_bitrate'],
            '-ar', (string) $r['audio_sample_rate'],
            '-ac', (string) $r['audio_channels'],
            '-movflags', '+faststart',
        ];
    }
}
