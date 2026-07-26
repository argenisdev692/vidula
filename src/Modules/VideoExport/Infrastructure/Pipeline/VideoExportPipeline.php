<?php

declare(strict_types=1);

namespace Modules\VideoExport\Infrastructure\Pipeline;

use Carbon\CarbonImmutable;
use Modules\VideoExport\Application\DTOs\EnqueueExportData;
use Modules\VideoExport\Application\Services\VideoExportJobStore;
use Modules\VideoExport\Domain\Enums\AudioEnhanceMode;
use Modules\VideoExport\Domain\Enums\ExportMode;
use Modules\VideoExport\Domain\Ports\AudioDenoisePort;
use Modules\VideoExport\Domain\Services\CutPlanner;
use Modules\VideoExport\Domain\Services\FillerCutDetector;
use Modules\VideoExport\Domain\Services\SilenceCutParser;
use Modules\VideoExport\Domain\ValueObjects\TimeRange;
use Shared\Domain\Ports\StoragePort;
use Throwable;

final readonly class VideoExportPipeline
{
    public function __construct(
        private InputResolver $resolver,
        private VideoWorkspace $workspace,
        private FfmpegBinaryRunner $ffmpeg,
        private SilenceCutParser $silenceParser,
        private CutPlanner $cutPlanner,
        private AudioEnhanceChain $audioEnhance,
        private AudioDenoisePort $audioDenoise,
        private OpenAiWhisperTranscriber $whisper,
        private ScriptReviewService $scriptReview,
        private StoragePort $storage,
        private VideoExportJobStore $jobs,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function run(EnqueueExportData $data, int|string $userId): array
    {
        $jobUuid = $data->jobUuid;
        $this->jobs->put([
            'job_uuid' => $jobUuid,
            'user_id' => $userId,
            'status' => 'active',
            'mode' => $data->mode,
            'result' => null,
            'error' => null,
            'updated_at' => now()->toIso8601String(),
        ]);

        try {
            $paths = $this->resolver->resolveAll(
                $jobUuid,
                $data->videoPaths,
                $data->sortByCreationTime,
            );

            $result = match ($data->exportMode()) {
                ExportMode::Merge => $this->runMerge($jobUuid, $paths, $data->resolveLowMemory()),
                ExportMode::Clean, ExportMode::Ai => $this->runClean($jobUuid, $paths, $data),
            };

            $this->jobs->put([
                'job_uuid' => $jobUuid,
                'user_id' => $userId,
                'status' => 'completed',
                'mode' => $data->mode,
                'result' => $result,
                'error' => null,
                'updated_at' => now()->toIso8601String(),
            ]);

            return $result;
        } catch (Throwable $e) {
            $this->jobs->markFailed($jobUuid, 'Export failed. Check logs for details.');
            throw $e;
        } finally {
            $this->workspace->wipe($jobUuid);
        }
    }

    /**
     * @param  list<string>  $paths
     * @return array<string, mixed>
     */
    private function runMerge(string $jobUuid, array $paths, bool $lowMemory): array
    {
        $outDir = $this->workspace->path($jobUuid, 'export');
        $this->workspace->ensureDir($outDir);
        $outPath = $outDir.DIRECTORY_SEPARATOR.'export.mp4';
        $this->ffmpeg->mergeAndRender($paths, $outPath, $lowMemory);
        $duration = $this->ffmpeg->probeDurationSeconds($outPath);
        $storageUrl = $this->uploadResult($jobUuid, $outPath);
        $this->deleteSourceParts($paths);

        return [
            'job_uuid' => $jobUuid,
            'status' => 'completed',
            'storage_url' => $storageUrl,
            'duration_seconds' => round($duration, 3),
            'diagnostics' => [
                'source_count' => count($paths),
                'merged' => count($paths) > 1,
                'mode' => ExportMode::Merge->value,
                'low_memory' => $lowMemory,
            ],
        ];
    }

    /**
     * @param  list<string>  $paths
     * @return array<string, mixed>
     */
    private function runClean(string $jobUuid, array $paths, EnqueueExportData $data): array
    {
        $prepared = $this->workspace->path($jobUuid, 'prepared');
        $this->workspace->ensureDir($prepared);
        $working = $prepared.DIRECTORY_SEPARATOR.'merged.mp4';
        $lowMemory = $data->resolveLowMemory();

        if (count($paths) === 1) {
            copy(array_first($paths), $working);
        } else {
            $this->ffmpeg->mergeVideos($paths, $working, $lowMemory);
        }

        $duration = $this->ffmpeg->probeDurationSeconds($working);
        $stderr = $this->ffmpeg->detectSilenceStderr($working, $data->silenceThresholdSeconds);
        $silenceCuts = $this->silenceParser->parse($stderr, $duration);

        $fillerCuts = [];
        $stutterCuts = [];
        $pauseCuts = [];
        $words = [];
        $review = null;
        $reviewError = null;
        $leftover = 0;

        $mode = $data->exportMode();
        $scriptProvided = filled($data->scriptPath);
        $enhanceMode = $data->resolveAudioEnhanceMode();

        if ($mode->usesSpeechAi() || $scriptProvided) {
            $audioPath = $prepared.DIRECTORY_SEPARATOR.'whisper.mp3';
            $this->ffmpeg->extractWhisperAudio($working, $audioPath);
            $words = $this->whisper->transcribeWords($audioPath, $data->language);

            if ($mode->usesSpeechAi()) {
                $detector = new FillerCutDetector(
                    fillerTerms: array_values(config('video-export.filler_terms', [])),
                    pauseKeywords: array_values(config('video-export.pause_keywords', [])),
                    pauseBacktrack: config('video-export.pause_backtrack', [
                        'silence_threshold_seconds' => 0.4,
                        'max_seconds' => 8.0,
                    ]),
                    stutter: config('video-export.stutter', [
                        'max_gap_seconds' => 0.4,
                        'max_token_chars' => 5,
                    ]),
                    minSegmentSeconds: (float) config('video-export.min_segment_seconds', 0.25),
                );
                $fillerCuts = $detector->findFillerCuts($words);
                $stutterCuts = $detector->findStutterCuts($words);
                $pauseCuts = $detector->findPauseCuts($words);
            }

            if ($scriptProvided) {
                $transcript = implode(' ', array_map(
                    static fn ($w) => $w->text,
                    $words,
                ));
                $reviewed = $this->scriptReview->review(
                    $data->scriptPath,
                    $transcript,
                    $data->aiProvider,
                );
                $review = $reviewed['review'] !== '' ? $reviewed['review'] : null;
                $leftover = $reviewed['leftover_pause_fragments'];
                $reviewError = $reviewed['error'] ?? null;
            }
        }

        /** @var list<TimeRange> $allCuts */
        $allCuts = [...$silenceCuts, ...$fillerCuts, ...$stutterCuts, ...$pauseCuts];
        $keep = $this->cutPlanner->invertCuts($allCuts, $duration);

        $outDir = $this->workspace->path($jobUuid, 'export');
        $this->workspace->ensureDir($outDir);
        $outPath = $outDir.DIRECTORY_SEPARATOR.'export.mp4';

        $this->renderWithEnhanceMode($working, $keep, $outPath, $prepared, $enhanceMode, $lowMemory);

        $finalDuration = $this->cutPlanner->totalDuration($keep);
        $storageUrl = $this->uploadResult($jobUuid, $outPath);
        $this->deleteSourceParts($paths);

        $payload = [
            'job_uuid' => $jobUuid,
            'status' => 'completed',
            'storage_url' => $storageUrl,
            'duration_seconds' => $finalDuration,
            'silence_cuts' => count($silenceCuts),
            'diagnostics' => [
                'source_count' => count($paths),
                'merged' => count($paths) > 1,
                'original_duration_seconds' => round($duration, 3),
                'silence_cuts' => count($silenceCuts),
                'filler_cuts' => count($fillerCuts),
                'stutter_cuts' => count($stutterCuts),
                'pause_cuts' => count($pauseCuts),
                'keep_segments' => count($keep),
                'ai_cleaning_enabled' => $mode->usesSpeechAi(),
                'ai_provider' => $data->aiProvider,
                'audio_enhanced' => $enhanceMode->isEnabled(),
                'audio_enhance_mode' => $enhanceMode->value,
                'script_reviewed' => $scriptProvided && $reviewError === null,
                'leftover_pause_fragments' => $leftover,
                'review_error' => $reviewError,
                'low_memory' => $lowMemory,
            ],
        ];

        if ($review !== null) {
            $payload['review'] = $review;
        }

        return $payload;
    }

    /**
     * @param  list<TimeRange>  $keep
     */
    private function renderWithEnhanceMode(
        string $working,
        array $keep,
        string $outPath,
        string $preparedDir,
        AudioEnhanceMode $enhanceMode,
        bool $lowMemory = false,
    ): void {
        if ($enhanceMode->usesAiDenoise()) {
            $cutOnly = $preparedDir.DIRECTORY_SEPARATOR.'cut.mp4';
            $rawWav = $preparedDir.DIRECTORY_SEPARATOR.'raw.wav';
            $cleanWav = $preparedDir.DIRECTORY_SEPARATOR.'clean.wav';

            $this->ffmpeg->render($working, $keep, $cutOnly, null, $lowMemory);
            $this->ffmpeg->extractWav($cutOnly, $rawWav);
            $this->audioDenoise->enhance($rawWav, $cleanWav);
            $this->ffmpeg->replaceAudioTrack(
                $cutOnly,
                $cleanWav,
                $outPath,
                $this->audioEnhance->buildPostAi(),
            );

            return;
        }

        $dsp = $enhanceMode->usesDsp() ? $this->audioEnhance->build() : null;
        $this->ffmpeg->render($working, $keep, $outPath, $dsp, $lowMemory);
    }

    private function uploadResult(string $jobUuid, string $localPath): ?string
    {
        $key = rtrim((string) config('video-export.result_prefix'), '/').'/'.$jobUuid.'/export.mp4';
        $this->storage->putFromPath($key, $localPath, 'private');

        return $this->storage->temporaryUrl($key, CarbonImmutable::now()->addDay());
    }

    /**
     * @param  list<string>  $localPaths
     */
    private function deleteSourceParts(array $localPaths): void
    {
        // Local workspace wipe happens in finally; R2 staging keys are best-effort
        // deleted when public URLs map under upload_parts_prefix (future enhancement).
        unset($localPaths);
    }
}
