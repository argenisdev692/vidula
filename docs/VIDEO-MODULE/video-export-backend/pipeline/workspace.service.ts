import { Injectable } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import { mkdir, rm } from 'node:fs/promises';
import { join } from 'node:path';
import { tmpdir } from 'node:os';
import { LoggerService } from '../../../logger/logger.service';

/**
 * Owns the local scratch filesystem for the pipeline. Every job gets its own
 * directory under the work root; the whole tree is deleted after the render so
 * intermediates (downloaded parts, extracted audio, merge temp) never linger —
 * only the final MP4 reaches R2.
 */
@Injectable()
export class WorkspaceService {
  private readonly root: string;

  constructor(
    config: ConfigService,
    private readonly logger: LoggerService,
  ) {
    this.logger.setContext(WorkspaceService.name);
    this.root =
      config.get<string>('VIDEO_EXPORT_WORK_DIR') ??
      join(tmpdir(), 'vidula', 'video-export');
  }

  jobDir(jobUuid: string): string {
    return join(this.root, jobUuid);
  }

  path(jobUuid: string, ...segments: string[]): string {
    return join(this.jobDir(jobUuid), ...segments);
  }

  async ensureDir(dir: string): Promise<void> {
    await mkdir(dir, { recursive: true });
  }

  /** Best-effort removal of the whole job tree — never throws. */
  async cleanupJob(jobUuid: string): Promise<void> {
    try {
      await rm(this.jobDir(jobUuid), { recursive: true, force: true });
    } catch (error) {
      this.logger.warn('WorkspaceService.cleanupJob failed', {
        jobUuid,
        error: error instanceof Error ? error.message : String(error),
      });
    }
  }
}
