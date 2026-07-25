import { Processor, WorkerHost } from '@nestjs/bullmq';
import { Inject, Injectable } from '@nestjs/common';
import { Job } from 'bullmq';
import { ClsService } from 'nestjs-cls';
import { LoggerService } from '../../logger/logger.service';
import { CLS_KEYS } from '../../shared/cls/cls.constants';
import {
  AUDIT_PORT,
  type IAuditPort,
} from '../../shared/activity-log/audit.port';
import { QUEUE_NAMES } from '../../shared/messaging/queues.constants';
import { VIDEO_EXPORT_JOBS } from './video-export.constants';
import type {
  CleanJobData,
  CleanResult,
  MergeJobData,
  MergeResult,
  ResolvedInput,
  TimeRange,
  WordTimestamp,
} from './video-export.types';
import { FfmpegService } from './pipeline/ffmpeg.service';
import { SilenceService } from './pipeline/silence.service';
import { TranscriptionService } from './pipeline/transcription.service';
import { FillerService } from './pipeline/filler.service';
import { CutPlannerService } from './pipeline/cut-planner.service';
import { AudioEnhanceService } from './pipeline/audio-enhance.service';
import { InputResolverService } from './pipeline/input-resolver.service';
import { R2VideoStorageService } from './pipeline/r2-video-storage.service';
import { WorkspaceService } from './pipeline/workspace.service';
import { ScriptReviewService } from './pipeline/script-review.service';
import { ReviewPdfService } from './pipeline/review-pdf.service';

/**
 * BullMQ worker for the video-export pipeline. Heavy ffmpeg work runs here, off
 * the request thread. Concurrency is pinned to 1 because each job saturates CPU
 * during merge/render — raise it only on a dedicated worker box.
 */
@Injectable()
@Processor(QUEUE_NAMES.VIDEO_EXPORT, { concurrency: 1 })
export class VideoExportProcessor extends WorkerHost {
  constructor(
    private readonly resolver: InputResolverService,
    private readonly ffmpeg: FfmpegService,
    private readonly silence: SilenceService,
    private readonly transcription: TranscriptionService,
    private readonly filler: FillerService,
    private readonly cutPlanner: CutPlannerService,
    private readonly audioEnhance: AudioEnhanceService,
    private readonly storage: R2VideoStorageService,
    private readonly workspace: WorkspaceService,
    private readonly scriptReview: ScriptReviewService,
    private readonly reviewPdf: ReviewPdfService,
    private readonly logger: LoggerService,
    private readonly cls: ClsService,
    @Inject(AUDIT_PORT) private readonly audit: IAuditPort,
  ) {
    super();
    this.logger.setContext(VideoExportProcessor.name);
  }

  async process(
    job: Job<CleanJobData | MergeJobData>,
  ): Promise<CleanResult | MergeResult> {
    // The BullMQ worker runs outside the HTTP request lifecycle, so no CLS
    // context exists here. Establish one keyed by the job so LoggerService
    // auto-enriches every line — including the pipeline services — with the
    // same traceId/userId for end-to-end correlation.
    return this.cls.run(() => {
      this.cls.set(CLS_KEYS.TRACE_ID, job.id ?? job.data.jobUuid);
      this.cls.set(CLS_KEYS.USER_ID, job.data.userId);
      return job.name === VIDEO_EXPORT_JOBS.MERGE
        ? this.runMerge(job as Job<MergeJobData>)
        : this.runClean(job as Job<CleanJobData>);
    });
  }

  // ── Cleaning flow ──────────────────────────────────────────────────────────

  private async runClean(job: Job<CleanJobData>): Promise<CleanResult> {
    const data = job.data;
    const traceId = this.cls.get<string>('traceId') ?? job.id ?? data.jobUuid;
    this.logger.info('VideoExportProcessor.clean start', {
      traceId,
      jobUuid: data.jobUuid,
      sources: data.videoPaths.length,
      aiCleaningEnabled: data.aiCleaningEnabled,
    });

    try {
      const resolved = await this.resolver.resolveAll(
        data.jobUuid,
        data.videoPaths,
        data.sortByCreationTime,
      );
      const workingVideo = await this.toWorkingVideo(data.jobUuid, resolved);
      const duration = await this.ffmpeg.probeDurationSeconds(workingVideo);

      const silenceCuts = await this.silence.detectSilenceCuts(
        workingVideo,
        data.silenceThresholdSeconds,
        duration,
      );

      let fillerCuts: TimeRange[] = [];
      let stutterCuts: TimeRange[] = [];
      let pauseCuts: TimeRange[] = [];
      let words: WordTimestamp[] = [];

      // Transcribe when AI cleaning is on OR a script was sent for the review.
      const scriptProvided = Boolean(data.scriptPath);
      if (data.aiCleaningEnabled || scriptProvided) {
        const preparedDir = this.workspace.path(data.jobUuid, 'prepared');
        await this.workspace.ensureDir(preparedDir);
        const audioPath = `${preparedDir}/whisper.mp3`;
        await this.ffmpeg.extractWhisperAudio(workingVideo, audioPath);
        words = await this.transcription.transcribeWords(
          audioPath,
          data.language,
        );
        if (data.aiCleaningEnabled) {
          if (data.detectFillers)
            fillerCuts = this.filler.findFillerCuts(words);
          if (data.detectStutters)
            stutterCuts = this.filler.findStutterCuts(words);
          if (data.detectPause) pauseCuts = this.filler.findPauseCuts(words);
        }
      }

      const keep = this.cutPlanner.invertCuts(
        [...silenceCuts, ...fillerCuts, ...stutterCuts, ...pauseCuts],
        duration,
      );

      const outDir = this.workspace.path(data.jobUuid, 'export');
      await this.workspace.ensureDir(outDir);
      const outPath = `${outDir}/export.mp4`;
      await this.ffmpeg.render(
        workingVideo,
        keep,
        outPath,
        data.audioEnhancementEnabled
          ? this.audioEnhance.buildChain()
          : undefined,
      );

      const { storageUrl, uploadError } = await this.tryUpload(
        data.jobUuid,
        outPath,
        'export.mp4',
      );

      const finalDuration = this.cutPlanner.totalDuration(keep);
      const review = scriptProvided
        ? await this.runReview(data, words, keep, {
            originalDurationSeconds: Math.round(duration * 1000) / 1000,
            finalDurationSeconds: finalDuration,
            silenceCuts: silenceCuts.length,
            fillerCuts: fillerCuts.length,
            stutterCuts: stutterCuts.length,
            pauseCuts: pauseCuts.length,
          })
        : null;

      await this.storage.deleteSourceParts(data.videoPaths);

      const result: CleanResult = {
        job_uuid: data.jobUuid,
        status: 'completed',
        storage_url: storageUrl,
        duration_seconds: finalDuration,
        silence_cuts: silenceCuts.length,
        ...(review?.text ? { review: review.text } : {}),
        diagnostics: {
          source_count: data.videoPaths.length,
          merged: resolved.length > 1,
          merge_order: resolved.map((r) => r.reference),
          original_duration_seconds: Math.round(duration * 1000) / 1000,
          silence_cuts: silenceCuts.length,
          filler_cuts: fillerCuts.length,
          stutter_cuts: stutterCuts.length,
          pause_cuts: pauseCuts.length,
          keep_segments: keep.length,
          ai_cleaning_enabled: data.aiCleaningEnabled,
          audio_enhanced: data.audioEnhancementEnabled,
          script_reviewed: scriptProvided,
          ...(review
            ? {
                leftover_pause_fragments: review.leftoverPause,
                review_pdf_url: review.pdfUrl,
                ...(review.error ? { review_error: review.error } : {}),
              }
            : {}),
          ...(uploadError ? { r2_upload_error: uploadError } : {}),
        },
      };

      await this.logCompletion(
        'video_export.completed',
        data.jobUuid,
        data.userId,
        {
          silenceCuts: silenceCuts.length,
          fillerCuts: fillerCuts.length,
        },
      );
      this.logger.info('VideoExportProcessor.clean end', {
        traceId,
        jobUuid: data.jobUuid,
        keepSegments: keep.length,
      });
      return result;
    } catch (error) {
      await this.logFailure(data.jobUuid, data.userId, error);
      throw error;
    } finally {
      await this.workspace.cleanupJob(data.jobUuid);
    }
  }

  // ── Merge-only flow ─────────────────────────────────────────────────────────

  private async runMerge(job: Job<MergeJobData>): Promise<MergeResult> {
    const data = job.data;
    const traceId = this.cls.get<string>('traceId') ?? job.id ?? data.jobUuid;
    this.logger.info('VideoExportProcessor.merge start', {
      traceId,
      jobUuid: data.jobUuid,
      sources: data.videoPaths.length,
    });

    try {
      const resolved = await this.resolver.resolveAll(
        data.jobUuid,
        data.videoPaths,
        data.sortByCreationTime,
      );

      const outDir = this.workspace.path(data.jobUuid, 'merge-export');
      await this.workspace.ensureDir(outDir);
      const outPath = `${outDir}/merge-export.mp4`;
      await this.ffmpeg.mergeAndRender(
        resolved.map((r) => r.localPath),
        outPath,
      );

      const duration = await this.ffmpeg.probeDurationSeconds(outPath);
      const { storageUrl, uploadError } = await this.tryUpload(
        data.jobUuid,
        outPath,
        'merge-export.mp4',
      );
      await this.storage.deleteSourceParts(data.videoPaths);

      const result: MergeResult = {
        job_uuid: data.jobUuid,
        status: 'completed',
        storage_url: storageUrl,
        duration_seconds: Math.round(duration * 1000) / 1000,
        diagnostics: {
          source_count: data.videoPaths.length,
          merged: resolved.length > 1,
          merge_order: resolved.map((r) => r.reference),
          ...(uploadError ? { r2_upload_error: uploadError } : {}),
        },
      };

      await this.logCompletion(
        'video_export.merge_completed',
        data.jobUuid,
        data.userId,
        {
          sources: data.videoPaths.length,
        },
      );
      this.logger.info('VideoExportProcessor.merge end', {
        traceId,
        jobUuid: data.jobUuid,
      });
      return result;
    } catch (error) {
      await this.logFailure(data.jobUuid, data.userId, error);
      throw error;
    } finally {
      await this.workspace.cleanupJob(data.jobUuid);
    }
  }

  // ── helpers ──────────────────────────────────────────────────────────────

  /** Merge multiple parts into one near-lossless working file, or pass through one. */
  private async toWorkingVideo(
    jobUuid: string,
    resolved: ResolvedInput[],
  ): Promise<string> {
    if (resolved.length === 1) return resolved[0].localPath;
    const mergeDir = this.workspace.path(jobUuid, 'merged');
    await this.workspace.ensureDir(mergeDir);
    const mergedPath = `${mergeDir}/merged.mp4`;
    await this.ffmpeg.mergeVideos(
      resolved.map((r) => r.localPath),
      mergedPath,
    );
    return mergedPath;
  }

  /**
   * Gemini script review (best-effort — a review failure never fails the
   * export; the MP4 is the primary deliverable). Reconstructs the final
   * transcript from the kept words, flags any residual "pausa", renders the
   * report to PDF and uploads it to R2.
   */
  private async runReview(
    data: CleanJobData,
    words: WordTimestamp[],
    keep: TimeRange[],
    diagnostics: {
      originalDurationSeconds: number;
      finalDurationSeconds: number;
      silenceCuts: number;
      fillerCuts: number;
      stutterCuts: number;
      pauseCuts: number;
    },
  ): Promise<{
    text?: string;
    pdfUrl: string | null;
    leftoverPause: number;
    error?: string;
  }> {
    const keptWords = words.filter((w) =>
      keep.some((r) => w.start >= r.start && w.start < r.end),
    );
    const leftoverPause = this.filler.findPauseCuts(keptWords).length;

    try {
      const scriptPath = data.scriptPath as string;
      const bytes = await this.resolver.fetchScriptBytes(scriptPath);
      const format = this.inferScriptFormat(scriptPath, data.scriptFormat);
      const text = await this.scriptReview.review({
        scriptText: format === 'markdown' ? bytes.toString('utf8') : undefined,
        scriptPdfBase64:
          format === 'pdf' ? bytes.toString('base64') : undefined,
        words: keptWords,
        diagnostics: { ...diagnostics, leftoverPauseFragments: leftoverPause },
      });
      const pdf = await this.reviewPdf.build({
        jobUuid: data.jobUuid,
        reviewMarkdown: text,
        generatedAt: new Date(),
      });
      const pdfUrl = await this.storage.uploadReviewPdf(data.jobUuid, pdf);
      return { text, pdfUrl, leftoverPause };
    } catch (error) {
      const message = error instanceof Error ? error.message : String(error);
      this.logger.error('VideoExportProcessor review failed', {
        jobUuid: data.jobUuid,
        error: message,
      });
      return { pdfUrl: null, leftoverPause, error: message };
    }
  }

  private inferScriptFormat(
    path: string,
    explicit?: 'markdown' | 'pdf',
  ): 'markdown' | 'pdf' {
    if (explicit) return explicit;
    const clean = path.split('?')[0].toLowerCase();
    return clean.endsWith('.pdf') ? 'pdf' : 'markdown';
  }

  private async tryUpload(
    jobUuid: string,
    localPath: string,
    filename: 'export.mp4' | 'merge-export.mp4',
  ): Promise<{ storageUrl: string | null; uploadError?: string }> {
    try {
      const storageUrl = await this.storage.uploadFinal(
        jobUuid,
        localPath,
        filename,
      );
      return { storageUrl };
    } catch (error) {
      const uploadError =
        error instanceof Error ? error.message : String(error);
      this.logger.error('VideoExportProcessor R2 upload failed', {
        jobUuid,
        uploadError,
      });
      return { storageUrl: null, uploadError };
    }
  }

  private async logCompletion(
    action: string,
    jobUuid: string,
    userId: string,
    metadata: Record<string, unknown>,
  ): Promise<void> {
    // Fire-and-forget (strict:false) — a job processor must never abort on an
    // audit write, and there is no DB transaction here to roll back.
    await this.audit.log(
      {
        action,
        actorId: userId,
        resourceType: 'VIDEO_EXPORT',
        resourceId: jobUuid,
        metadata,
      },
      { strict: false },
    );
  }

  private async logFailure(
    jobUuid: string,
    userId: string,
    error: unknown,
  ): Promise<void> {
    this.logger.error('VideoExportProcessor failed', {
      jobUuid,
      error: error instanceof Error ? error.message : String(error),
    });
    await this.audit.log(
      {
        action: 'video_export.failed',
        actorId: userId,
        resourceType: 'VIDEO_EXPORT',
        resourceId: jobUuid,
        metadata: {
          error: error instanceof Error ? error.message : String(error),
        },
      },
      { strict: false },
    );
  }
}
