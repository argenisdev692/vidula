import { Injectable } from '@nestjs/common';
import { readFile } from 'node:fs/promises';
import { LoggerService } from '../../../logger/logger.service';
import { StorageService } from '../../../shared/storage/storage.service';

/**
 * R2 side of the pipeline: uploads the final cleaned/merged MP4 and deletes the
 * temporary source parts the frontend uploaded. Only the final result is kept
 * in the bucket.
 *
 * NOTE: the shared StorageService takes a Buffer, so the final file is read
 * into memory before upload. For multi-hour HD exports a streaming multipart
 * upload would be preferable (follow-up).
 */
@Injectable()
export class R2VideoStorageService {
  constructor(
    private readonly storage: StorageService,
    private readonly logger: LoggerService,
  ) {
    this.logger.setContext(R2VideoStorageService.name);
  }

  /** Upload the final render. Returns the public URL. Throws on failure. */
  async uploadFinal(
    jobUuid: string,
    localPath: string,
    filename: 'export.mp4' | 'merge-export.mp4',
  ): Promise<string> {
    const key = `video-exports/${jobUuid}/${filename}`;
    const buffer = await readFile(localPath);
    await this.storage.upload(key, buffer, 'video/mp4');
    const url = this.storage.publicUrl(key);
    this.logger.info('R2VideoStorageService.uploadFinal', { jobUuid, key });
    return url;
  }

  /** Upload the Gemini review PDF. Returns the public URL. Throws on failure. */
  async uploadReviewPdf(jobUuid: string, buffer: Buffer): Promise<string> {
    const key = `video-exports/${jobUuid}/review.pdf`;
    await this.storage.upload(key, buffer, 'application/pdf');
    const url = this.storage.publicUrl(key);
    this.logger.info('R2VideoStorageService.uploadReviewPdf', { jobUuid, key });
    return url;
  }

  /**
   * Best-effort deletion of the uploaded source parts (Pattern 5 — never
   * rethrows). Only deletes references that belong to this bucket; external
   * URLs are skipped.
   */
  async deleteSourceParts(references: string[]): Promise<void> {
    for (const reference of references) {
      if (!/^https?:\/\//i.test(reference)) continue;
      try {
        const key = this.storage.keyFromUrl(reference);
        await this.storage.delete(key);
        this.logger.info('R2VideoStorageService.deleteSourcePart', { key });
      } catch (error) {
        // Not bucket-owned, or delete failed — log and move on.
        this.logger.warn('R2VideoStorageService.deleteSourcePart skipped', {
          reference,
          error: error instanceof Error ? error.message : String(error),
        });
      }
    }
  }
}
