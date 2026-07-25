import { z } from 'zod';
import { createZodDto } from 'nestjs-zod';

const TWO_GB = 2 * 1024 * 1024 * 1024;

/**
 * `POST /video-export/uploads/presign` — request a short-lived presigned PUT URL
 * so the browser can upload ONE source video part directly to R2, bypassing the
 * API (the bytes never flow through NestJS). The returned public URL is then
 * sent back in `video_paths` when the export job is created.
 *
 * Only `video/*` content types are accepted: the pipeline merges/renders these
 * and the temporary parts are deleted by the worker once the final MP4 is up.
 */
export const PresignUploadSchema = z.object({
  /** Original file name — used only to derive the object extension/label. */
  filename: z.string().min(1).max(255),

  /** MIME type; bound into the SigV4 signature and re-sent on the PUT. */
  content_type: z
    .string()
    .min(1)
    .max(128)
    .regex(/^video\//i, 'Only video/* content types are allowed'),

  /** Optional client-reported size, capped to the 2 GB per-part download limit. */
  size_bytes: z.number().int().positive().max(TWO_GB).optional(),
});

export class PresignUploadDto extends createZodDto(PresignUploadSchema) {}

export const PresignUploadResponseSchema = z.object({
  /** Short-lived URL the browser PUTs the file to (write-once to this key). */
  upload_url: z.string().url(),

  /** Permanent public URL the object will have once uploaded. */
  public_url: z.string().url(),

  /** R2 object key the part was assigned. */
  key: z.string(),

  /** Lifetime of `upload_url` in seconds. */
  expires_in_seconds: z.number().int().positive(),
});

export class PresignUploadResponseDto extends createZodDto(
  PresignUploadResponseSchema,
) {}
