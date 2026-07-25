import { Test } from '@nestjs/testing';
import { getQueueToken } from '@nestjs/bullmq';
import { ConfigService } from '@nestjs/config';
import { ServiceUnavailableException } from '@nestjs/common';
import { ClsService } from 'nestjs-cls';
import { LoggerService } from '../../../logger/logger.service';
import { CaslAbilityFactory } from '../../../core/access/casl-ability.factory';
import { AUDIT_PORT } from '../../../shared/activity-log/audit.port';
import { StorageService } from '../../../shared/storage/storage.service';
import { QUEUE_NAMES } from '../../../shared/messaging/queues.constants';
import { VideoExportService } from '../video-export.service';
import { VIDEO_EXPORT_JOBS } from '../video-export.constants';
import type { ExportRequestDto } from '../dto/export-request.dto';
import type { AuthenticatedUser } from '../../../core/access/actions.enum';

const owner: AuthenticatedUser = {
  id: 'user-1',
  roleIds: [],
  roleNames: [],
};

describe('VideoExportService', () => {
  const queue = {
    getJob: jest.fn(),
    add: jest.fn(),
  };
  const logger = {
    setContext: jest.fn(),
    info: jest.fn(),
    warn: jest.fn(),
    error: jest.fn(),
    debug: jest.fn(),
  };
  const cls = { get: jest.fn().mockReturnValue('trace-1') };
  // Default: OPENAI_API_KEY is configured so AI-cleaning jobs are accepted.
  const config = { get: jest.fn().mockReturnValue('sk-test') };
  // Default ability: cannot `manage` — so the BOLA guard relies on ownership.
  const abilityFactory = {
    createForUser: jest.fn().mockResolvedValue({ can: () => false }),
  };
  const audit = { log: jest.fn() };
  const storage = {
    presignedPutUrl: jest.fn().mockResolvedValue({
      uploadUrl: 'https://r2.upload/signed?sig=abc',
      publicUrl: 'https://cdn/video-exports/_parts/uuid/part.mp4',
      expiresInSeconds: 900,
    }),
  };

  let service: VideoExportService;

  beforeEach(async () => {
    jest.clearAllMocks();
    config.get.mockReturnValue('sk-test');
    abilityFactory.createForUser.mockResolvedValue({ can: () => false });
    const moduleRef = await Test.createTestingModule({
      providers: [
        VideoExportService,
        { provide: getQueueToken(QUEUE_NAMES.VIDEO_EXPORT), useValue: queue },
        { provide: LoggerService, useValue: logger },
        { provide: ClsService, useValue: cls },
        { provide: ConfigService, useValue: config },
        { provide: CaslAbilityFactory, useValue: abilityFactory },
        { provide: AUDIT_PORT, useValue: audit },
        { provide: StorageService, useValue: storage },
      ],
    }).compile();
    service = moduleRef.get(VideoExportService);
  });

  const cleanDto: ExportRequestDto = {
    job_uuid: '11111111-1111-1111-1111-111111111111',
    video_paths: ['https://cdn/part-1.mp4', 'https://cdn/part-2.mp4'],
    silence_threshold_seconds: 1,
    ai_cleaning_enabled: false,
    audio_enhancement_enabled: true,
    sort_by_creation_time: true,
    detect_fillers: true,
    detect_stutters: true,
    detect_pause: true,
    language: 'es',
  };

  it('enqueues a clean job with the job_uuid as jobId and maps the DTO', async () => {
    queue.getJob.mockResolvedValue(null);

    const res = await service.enqueueClean('user-1', cleanDto);

    expect(res).toEqual({ job_uuid: cleanDto.job_uuid, status: 'queued' });
    expect(queue.add).toHaveBeenCalledWith(
      VIDEO_EXPORT_JOBS.CLEAN,
      expect.objectContaining({
        jobUuid: cleanDto.job_uuid,
        silenceThresholdSeconds: 1,
        aiCleaningEnabled: false,
        audioEnhancementEnabled: true,
        userId: 'user-1',
      }),
      expect.objectContaining({ jobId: cleanDto.job_uuid }),
    );
  });

  it('forces ai_cleaning_enabled on when a script is provided', async () => {
    queue.getJob.mockResolvedValue(null);

    await service.enqueueClean('user-1', {
      ...cleanDto,
      ai_cleaning_enabled: false,
      script_path: 'https://cdn/guion.pdf',
    });

    expect(queue.add).toHaveBeenCalledWith(
      VIDEO_EXPORT_JOBS.CLEAN,
      expect.objectContaining({
        aiCleaningEnabled: true,
        scriptPath: 'https://cdn/guion.pdf',
      }),
      expect.objectContaining({ jobId: cleanDto.job_uuid }),
    );
  });

  it('rejects AI cleaning when OPENAI_API_KEY is not configured', async () => {
    config.get.mockReturnValue(undefined);
    queue.getJob.mockResolvedValue(null);

    await expect(
      service.enqueueClean('user-1', {
        ...cleanDto,
        ai_cleaning_enabled: true,
      }),
    ).rejects.toBeInstanceOf(ServiceUnavailableException);
    expect(queue.add).not.toHaveBeenCalled();
  });

  it('returns duplicate without enqueuing when the job already exists', async () => {
    queue.getJob.mockResolvedValue({ id: cleanDto.job_uuid });

    const res = await service.enqueueClean('user-1', cleanDto);

    expect(res.status).toBe('duplicate');
    expect(queue.add).not.toHaveBeenCalled();
  });

  it('returns not_found when polling an unknown job', async () => {
    queue.getJob.mockResolvedValue(null);
    expect(await service.getJobStatus('missing', owner)).toEqual({
      job_uuid: 'missing',
      status: 'not_found',
    });
  });

  it('returns the stored result for a completed job owned by the caller', async () => {
    const returnvalue = { job_uuid: 'x', status: 'completed', silence_cuts: 3 };
    queue.getJob.mockResolvedValue({
      data: { userId: owner.id },
      getState: jest.fn().mockResolvedValue('completed'),
      returnvalue,
    });

    const res = await service.getJobStatus('x', owner);
    expect(res.status).toBe('completed');
    expect(res.result).toBe(returnvalue);
  });

  it('surfaces failedReason for a failed job', async () => {
    queue.getJob.mockResolvedValue({
      data: { userId: owner.id },
      getState: jest.fn().mockResolvedValue('failed'),
      failedReason: 'ffmpeg exploded',
    });

    const res = await service.getJobStatus('x', owner);
    expect(res).toEqual({
      job_uuid: 'x',
      status: 'failed',
      error: 'ffmpeg exploded',
    });
  });

  it('maps waiting state to queued', async () => {
    queue.getJob.mockResolvedValue({
      data: { userId: owner.id },
      getState: jest.fn().mockResolvedValue('waiting'),
    });
    expect((await service.getJobStatus('x', owner)).status).toBe('queued');
  });

  it('hides another user job as not_found when the caller cannot manage', async () => {
    queue.getJob.mockResolvedValue({
      data: { userId: 'someone-else' },
      getState: jest.fn().mockResolvedValue('completed'),
      returnvalue: { storage_url: 'https://cdn/secret.mp4' },
    });

    const res = await service.getJobStatus('x', owner);
    expect(res).toEqual({ job_uuid: 'x', status: 'not_found' });
    expect(abilityFactory.createForUser).toHaveBeenCalledWith(owner);
  });

  it('lets a VIDEO_EXPORT manager read another user job', async () => {
    abilityFactory.createForUser.mockResolvedValue({ can: () => true });
    const returnvalue = { storage_url: 'https://cdn/any.mp4' };
    queue.getJob.mockResolvedValue({
      data: { userId: 'someone-else' },
      getState: jest.fn().mockResolvedValue('completed'),
      returnvalue,
    });

    const res = await service.getJobStatus('x', owner);
    expect(res.status).toBe('completed');
    expect(res.result).toBe(returnvalue);
  });

  it('audits video_export.queued on enqueue', async () => {
    queue.getJob.mockResolvedValue(null);
    await service.enqueueClean('user-1', cleanDto);
    expect(audit.log).toHaveBeenCalledWith(
      expect.objectContaining({
        action: 'video_export.queued',
        actorId: 'user-1',
        resourceType: 'VIDEO_EXPORT',
        resourceId: cleanDto.job_uuid,
      }),
      { strict: false },
    );
  });

  it('createUploadUrl returns a presigned slot under the parts prefix', async () => {
    const res = await service.createUploadUrl('user-1', {
      filename: 'my clip!.mp4',
      content_type: 'video/mp4',
      size_bytes: 12345,
    });

    expect(storage.presignedPutUrl).toHaveBeenCalledWith(
      expect.stringMatching(/^video-exports\/_parts\/[\w-]+\/my_clip_\.mp4$/),
      'video/mp4',
      expect.any(Number),
    );
    expect(res).toEqual({
      upload_url: 'https://r2.upload/signed?sig=abc',
      public_url: 'https://cdn/video-exports/_parts/uuid/part.mp4',
      key: expect.stringContaining('video-exports/_parts/'),
      expires_in_seconds: 900,
    });
  });

  it('createUploadUrl audits the presign as fire-and-forget', async () => {
    await service.createUploadUrl('user-1', {
      filename: 'clip.mp4',
      content_type: 'video/mp4',
    });
    expect(audit.log).toHaveBeenCalledWith(
      expect.objectContaining({
        action: 'video_export.upload_presigned',
        actorId: 'user-1',
        resourceType: 'VIDEO_EXPORT',
      }),
      { strict: false },
    );
  });
});
