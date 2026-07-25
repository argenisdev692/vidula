import { Test } from '@nestjs/testing';
import { ClsService } from 'nestjs-cls';
import { LoggerService } from '../../../logger/logger.service';
import { AUDIT_PORT } from '../../../shared/activity-log/audit.port';
import { VideoExportProcessor } from '../video-export.processor';
import { InputResolverService } from '../pipeline/input-resolver.service';
import { FfmpegService } from '../pipeline/ffmpeg.service';
import { SilenceService } from '../pipeline/silence.service';
import { TranscriptionService } from '../pipeline/transcription.service';
import { FillerService } from '../pipeline/filler.service';
import { CutPlannerService } from '../pipeline/cut-planner.service';
import { AudioEnhanceService } from '../pipeline/audio-enhance.service';
import { R2VideoStorageService } from '../pipeline/r2-video-storage.service';
import { WorkspaceService } from '../pipeline/workspace.service';
import { ScriptReviewService } from '../pipeline/script-review.service';
import { ReviewPdfService } from '../pipeline/review-pdf.service';
import { VIDEO_EXPORT_JOBS } from '../video-export.constants';
import type {
  CleanJobData,
  MergeJobData,
  ResolvedInput,
} from '../video-export.types';

/**
 * Covers the write path: every flow must emit a business audit row and log
 * INFO at start AND end, and a pipeline failure must still audit + rethrow.
 */
describe('VideoExportProcessor', () => {
  const resolved: ResolvedInput = {
    reference: 'https://cdn/a.mp4',
    localPath: '/tmp/a.mp4',
    downloaded: true,
    orderKey: 1,
  };

  const resolver = { resolveAll: jest.fn() };
  const ffmpeg = {
    probeDurationSeconds: jest.fn(),
    render: jest.fn(),
    mergeVideos: jest.fn(),
    mergeAndRender: jest.fn(),
  };
  const silence = { detectSilenceCuts: jest.fn() };
  const transcription = { transcribeWords: jest.fn() };
  const filler = {
    findFillerCuts: jest.fn(),
    findStutterCuts: jest.fn(),
    findPauseCuts: jest.fn(),
  };
  const cutPlanner = { invertCuts: jest.fn(), totalDuration: jest.fn() };
  const audioEnhance = { buildChain: jest.fn() };
  const storage = {
    uploadFinal: jest.fn(),
    uploadReviewPdf: jest.fn(),
    deleteSourceParts: jest.fn(),
  };
  const workspace = {
    path: jest.fn().mockReturnValue('/tmp/job'),
    ensureDir: jest.fn(),
    cleanupJob: jest.fn(),
  };
  const scriptReview = { review: jest.fn() };
  const reviewPdf = { build: jest.fn() };
  const logger = {
    setContext: jest.fn(),
    info: jest.fn(),
    warn: jest.fn(),
    error: jest.fn(),
    debug: jest.fn(),
  };
  const cls = {
    run: <T>(cb: () => T): T => cb(),
    set: jest.fn(),
    get: jest.fn().mockReturnValue('trace-1'),
  };
  const audit = { log: jest.fn() };

  let processor: VideoExportProcessor;

  beforeEach(async () => {
    jest.clearAllMocks();
    workspace.path.mockReturnValue('/tmp/job');

    const moduleRef = await Test.createTestingModule({
      providers: [
        VideoExportProcessor,
        { provide: InputResolverService, useValue: resolver },
        { provide: FfmpegService, useValue: ffmpeg },
        { provide: SilenceService, useValue: silence },
        { provide: TranscriptionService, useValue: transcription },
        { provide: FillerService, useValue: filler },
        { provide: CutPlannerService, useValue: cutPlanner },
        { provide: AudioEnhanceService, useValue: audioEnhance },
        { provide: R2VideoStorageService, useValue: storage },
        { provide: WorkspaceService, useValue: workspace },
        { provide: ScriptReviewService, useValue: scriptReview },
        { provide: ReviewPdfService, useValue: reviewPdf },
        { provide: LoggerService, useValue: logger },
        { provide: ClsService, useValue: cls },
        { provide: AUDIT_PORT, useValue: audit },
      ],
    }).compile();
    processor = moduleRef.get(VideoExportProcessor);
  });

  const cleanData: CleanJobData = {
    jobUuid: 'job-1',
    videoPaths: ['https://cdn/a.mp4'],
    silenceThresholdSeconds: 1,
    aiCleaningEnabled: false,
    audioEnhancementEnabled: false,
    sortByCreationTime: true,
    detectFillers: true,
    detectStutters: true,
    detectPause: true,
    language: 'es',
    userId: 'user-9',
  };

  const mergeData: MergeJobData = {
    jobUuid: 'job-2',
    videoPaths: ['https://cdn/a.mp4'],
    sortByCreationTime: true,
    userId: 'user-9',
  };

  it('clean flow audits completion and logs start + end', async () => {
    resolver.resolveAll.mockResolvedValue([resolved]);
    ffmpeg.probeDurationSeconds.mockResolvedValue(100);
    silence.detectSilenceCuts.mockResolvedValue([]);
    cutPlanner.invertCuts.mockReturnValue([{ start: 0, end: 100 }]);
    cutPlanner.totalDuration.mockReturnValue(100);
    storage.uploadFinal.mockResolvedValue('https://cdn/job-1/export.mp4');

    const result = await processor.process({
      id: 'job-1',
      name: VIDEO_EXPORT_JOBS.CLEAN,
      data: cleanData,
    } as never);

    expect(result.status).toBe('completed');
    expect(result.storage_url).toBe('https://cdn/job-1/export.mp4');
    expect(audit.log).toHaveBeenCalledWith(
      expect.objectContaining({
        action: 'video_export.completed',
        actorId: 'user-9',
        resourceType: 'VIDEO_EXPORT',
        resourceId: 'job-1',
      }),
      { strict: false },
    );
    expect(logger.info).toHaveBeenCalledWith(
      'VideoExportProcessor.clean start',
      expect.anything(),
    );
    expect(logger.info).toHaveBeenCalledWith(
      'VideoExportProcessor.clean end',
      expect.anything(),
    );
    expect(workspace.cleanupJob).toHaveBeenCalledWith('job-1');
  });

  it('merge flow audits merge_completed', async () => {
    resolver.resolveAll.mockResolvedValue([resolved]);
    ffmpeg.mergeAndRender.mockResolvedValue(undefined);
    ffmpeg.probeDurationSeconds.mockResolvedValue(42);
    storage.uploadFinal.mockResolvedValue('https://cdn/job-2/merge-export.mp4');

    const result = await processor.process({
      id: 'job-2',
      name: VIDEO_EXPORT_JOBS.MERGE,
      data: mergeData,
    } as never);

    expect(result.status).toBe('completed');
    expect(audit.log).toHaveBeenCalledWith(
      expect.objectContaining({
        action: 'video_export.merge_completed',
        resourceId: 'job-2',
      }),
      { strict: false },
    );
    expect(logger.info).toHaveBeenCalledWith(
      'VideoExportProcessor.merge start',
      expect.anything(),
    );
    expect(logger.info).toHaveBeenCalledWith(
      'VideoExportProcessor.merge end',
      expect.anything(),
    );
  });

  it('audits failure and rethrows when the pipeline throws', async () => {
    const boom = new Error('ffmpeg exploded');
    resolver.resolveAll.mockRejectedValue(boom);

    await expect(
      processor.process({
        id: 'job-1',
        name: VIDEO_EXPORT_JOBS.CLEAN,
        data: cleanData,
      } as never),
    ).rejects.toThrow('ffmpeg exploded');

    expect(audit.log).toHaveBeenCalledWith(
      expect.objectContaining({
        action: 'video_export.failed',
        resourceId: 'job-1',
        metadata: { error: 'ffmpeg exploded' },
      }),
      { strict: false },
    );
    expect(workspace.cleanupJob).toHaveBeenCalledWith('job-1');
  });
});
