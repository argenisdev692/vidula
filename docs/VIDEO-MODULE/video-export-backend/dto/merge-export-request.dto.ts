import { z } from 'zod';
import { createZodDto } from 'nestjs-zod';

/**
 * `POST /video-export-merge` — async merge-only job.
 *
 * Concatenates the parts and renders one HD MP4. No silence removal, no
 * Whisper, no audio enhancement.
 */
export const MergeExportRequestSchema = z.object({
  job_uuid: z.string().uuid(),
  video_paths: z.array(z.string().min(1).max(2048)).min(1).max(50),
  /** Same creation-time ordering convenience as the cleaning flow. */
  sort_by_creation_time: z.boolean().default(true),
});

export class MergeExportRequestDto extends createZodDto(
  MergeExportRequestSchema,
) {}
