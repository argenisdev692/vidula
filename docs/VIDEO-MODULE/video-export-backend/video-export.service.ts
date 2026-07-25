import { randomUUID } from 'node:crypto';
import { basename } from 'node:path';
import {
  Inject,
  Injectable,
  ServiceUnavailableException,
} from '@nestjs/common';
import { InjectQueue } from '@nestjs/bullmq';
import { ConfigService } from '@nestjs/config';
import { Queue } from 'bullmq';
import { ClsService } from 'nestjs-cls';
import { LoggerService } from '../../logger/logger.service';
import { CaslAbilityFactory } from '../../core/access/casl-ability.factory';
import { Action } from '../../core/access/actions.enum';
import type { AuthenticatedUser } from '../../core/access/actions.enum';
import {
  AUDIT_PORT,
  type IAuditPort,
} from '../../shared/activity-log/audit.port';
import { StorageService } from '../../shared/storage/storage.service';
import { QUEUE_NAMES } from '../../shared/messaging/queues.constants';
import {
  JOB_RETENTION,
  PRESIGN_EXPIRES_SECONDS,
  UPLOAD_PARTS_PREFIX,
  VIDEO_EXPORT_JOBS,
} from './video-export.constants';
import type { ExportRequestDto } from './dto/export-request.dto';
import type { MergeExportRequestDto } from './dto/merge-export-request.dto';
import type { PresignUploadDto } from './dto/presign-upload.dto';
import type {
  CleanJobData,
  CleanResult,
  EnqueueResponse,
  JobStatusResponse,
  MergeJobData,
  MergeResult,
  PresignUploadResult,
} from './video-export.types';

/**
 * Thin HTTP-facing service: validates nothing extra (Zod already did), enqueues
 * the heavy work onto the VIDEO_EXPORT BullMQ queue and exposes job status.
 * There is no DB and no Repository — job state lives entirely in Redis/BullMQ.
 */
@Injectable()
export class VideoExportService {
  constructor(
    @InjectQueue(QUEUE_NAMES.VIDEO_EXPORT) private readonly queue: Queue,
    private readonly logger: LoggerService,
    private readonly cls: ClsService,
    private readonly config: ConfigService,
    private readonly abilityFactory: CaslAbilityFactory,
    @Inject(AUDIT_PORT) private readonly audit: IAuditPort,
    private readonly storage: StorageService,
  ) {
    this.logger.setContext(VideoExportService.name);
  }

  /**
   * Issues a presigned PUT URL so the browser uploads a source video part
   * DIRECTLY to R2 (the bytes never flow through this API). The returned
   * `public_url` is what the client then passes back in `video_paths` when it
   * creates the export job. These parts are temporary — the worker deletes them
   * once the final render is uploaded (see `R2VideoStorageService.deleteSourceParts`).
   */
  async createUploadUrl(
    userId: string,
    dto: PresignUploadDto,
  ): Promise<PresignUploadResult> {
    const traceId = this.cls.get<string>('traceId');
    const key = `${UPLOAD_PARTS_PREFIX}/${randomUUID()}/${this.safeName(dto.filename)}`;

    const { uploadUrl, publicUrl, expiresInSeconds } =
      await this.storage.presignedPutUrl(
        key,
        dto.content_type,
        PRESIGN_EXPIRES_SECONDS,
      );

    this.logger.info('VideoExportService.createUploadUrl', {
      traceId,
      userId,
      key,
      contentType: dto.content_type,
    });

    // Fire-and-forget audit (strict:false): no DB tx to roll back, and a flaky
    // audit row must never block a user from obtaining an upload slot.
    await this.audit.log(
      {
        action: 'video_export.upload_presigned',
        actorId: userId,
        resourceType: 'VIDEO_EXPORT',
        resourceId: key,
        metadata: { contentType: dto.content_type, sizeBytes: dto.size_bytes },
      },
      { strict: false },
    );

    return {
      upload_url: uploadUrl,
      public_url: publicUrl,
      key,
      expires_in_seconds: expiresInSeconds,
    };
  }

  /** Sanitise the client filename to a safe, bounded R2 object label. */
  private safeName(filename: string): string {
    const base = basename(filename)
      .replace(/[^\w.-]/g, '_')
      .slice(-128);
    return base.length > 0 ? base : 'part.mp4';
  }

  async enqueueClean(
    userId: string,
    dto: ExportRequestDto,
  ): Promise<EnqueueResponse> {
    // Sending a script implies the full AI clean: the review only makes sense
    // on a video that already had fillers/stutters/PAUSA removed, so a guion
    // forces ai_cleaning_enabled on regardless of what the client sent.
    const aiCleaningEnabled =
      dto.ai_cleaning_enabled || Boolean(dto.script_path);
    // Fail fast: the worker needs OPENAI_API_KEY for the Whisper pass. Without
    // it the job would queue then fail mid-pipeline — reject synchronously so
    // the client gets an actionable error instead of a dead job to poll.
    if (aiCleaningEnabled && !this.config.get<string>('OPENAI_API_KEY')) {
      throw new ServiceUnavailableException(
        'AI cleaning (or script_path, which requires it) needs OPENAI_API_KEY to be configured on the server. Retry with ai_cleaning_enabled=false and no script_path, or contact an administrator.',
      );
    }

    const data: CleanJobData = {
      jobUuid: dto.job_uuid,
      videoPaths: dto.video_paths,
      silenceThresholdSeconds: dto.silence_threshold_seconds,
      aiCleaningEnabled,
      audioEnhancementEnabled: dto.audio_enhancement_enabled,
      sortByCreationTime: dto.sort_by_creation_time,
      detectFillers: dto.detect_fillers,
      detectStutters: dto.detect_stutters,
      detectPause: dto.detect_pause,
      language: dto.language,
      scriptPath: dto.script_path,
      scriptFormat: dto.script_format,
      userId,
    };
    return this.enqueue(VIDEO_EXPORT_JOBS.CLEAN, dto.job_uuid, data);
  }

  async enqueueMerge(
    userId: string,
    dto: MergeExportRequestDto,
  ): Promise<EnqueueResponse> {
    const data: MergeJobData = {
      jobUuid: dto.job_uuid,
      videoPaths: dto.video_paths,
      sortByCreationTime: dto.sort_by_creation_time,
      userId,
    };
    return this.enqueue(VIDEO_EXPORT_JOBS.MERGE, dto.job_uuid, data);
  }

  async getJobStatus(
    jobUuid: string,
    user: AuthenticatedUser,
  ): Promise<JobStatusResponse> {
    const traceId = this.cls.get<string>('traceId');
    this.logger.info('VideoExportService.getJobStatus', { traceId, jobUuid });

    const job = await this.queue.getJob(jobUuid);
    if (!job) return { job_uuid: jobUuid, status: 'not_found' };

    // BOLA guard (OWASP API #1): only the job's owner — or a principal that can
    // `manage` VIDEO_EXPORT — may read it. Non-owners get `not_found` so job
    // ids cannot be enumerated through the read permission.
    const ownerId = (job.data as CleanJobData | MergeJobData).userId;
    if (ownerId !== user.id) {
      const ability = await this.abilityFactory.createForUser(user);
      if (!ability.can(Action.Manage, 'VIDEO_EXPORT')) {
        this.logger.warn('VideoExportService.getJobStatus forbidden', {
          traceId,
          jobUuid,
          requesterId: user.id,
        });
        return { job_uuid: jobUuid, status: 'not_found' };
      }
    }

    const state = await job.getState();
    switch (state) {
      case 'completed':
        return {
          job_uuid: jobUuid,
          status: 'completed',
          result: job.returnvalue as CleanResult | MergeResult,
        };
      case 'failed':
        return {
          job_uuid: jobUuid,
          status: 'failed',
          error: job.failedReason ?? 'job failed',
        };
      case 'active':
        return { job_uuid: jobUuid, status: 'active' };
      case 'delayed':
        return { job_uuid: jobUuid, status: 'delayed' };
      default:
        // waiting | waiting-children | prioritized | unknown
        return { job_uuid: jobUuid, status: 'queued' };
    }
  }

  private async enqueue(
    name: string,
    jobUuid: string,
    data: CleanJobData | MergeJobData,
  ): Promise<EnqueueResponse> {
    const traceId = this.cls.get<string>('traceId');
    const existing = await this.queue.getJob(jobUuid);
    if (existing) {
      this.logger.warn('VideoExportService.enqueue duplicate', {
        traceId,
        jobUuid,
      });
      return {
        job_uuid: jobUuid,
        status: 'duplicate',
        detail: 'A job with this job_uuid already exists.',
      };
    }

    await this.queue.add(name, data, {
      jobId: jobUuid,
      removeOnComplete: JOB_RETENTION.removeOnComplete,
      removeOnFail: JOB_RETENTION.removeOnFail,
    });
    // Audit the user-initiated create. Fire-and-forget (strict:false): there is
    // no DB transaction here to roll back, and a flaky audit row must never
    // reject an already-queued job.
    await this.audit.log(
      {
        action: 'video_export.queued',
        actorId: data.userId,
        resourceType: 'VIDEO_EXPORT',
        resourceId: jobUuid,
        metadata: { name, sources: data.videoPaths.length },
      },
      { strict: false },
    );
    this.logger.info('VideoExportService.enqueue', { traceId, jobUuid, name });
    return { job_uuid: jobUuid, status: 'queued' };
  }
}
