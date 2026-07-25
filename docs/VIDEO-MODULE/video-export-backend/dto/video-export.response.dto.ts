import { z } from 'zod';
import { createZodDto } from 'nestjs-zod';

/**
 * Output presenters for Swagger. These mirror the in-memory contracts in
 * `video-export.types.ts` (`EnqueueResponse` / `JobStatusResponse`) so the
 * OpenAPI doc renders real response schemas instead of bare examples. The
 * runtime shapes are still produced by the service/processor; these schemas
 * are documentation-only and never used to parse output.
 */

export const EnqueueResponseSchema = z.object({
  job_uuid: z.string().uuid(),
  status: z.enum(['queued', 'duplicate']),
  detail: z.string().optional(),
});

const CleanDiagnosticsSchema = z.object({
  source_count: z.number().int(),
  merged: z.boolean(),
  merge_order: z.array(z.string()),
  original_duration_seconds: z.number(),
  silence_cuts: z.number().int(),
  filler_cuts: z.number().int(),
  stutter_cuts: z.number().int(),
  pause_cuts: z.number().int(),
  keep_segments: z.number().int(),
  ai_cleaning_enabled: z.boolean(),
  audio_enhanced: z.boolean(),
  script_reviewed: z.boolean(),
  leftover_pause_fragments: z.number().int().optional(),
  review_pdf_url: z.string().nullable().optional(),
  review_error: z.string().optional(),
  r2_upload_error: z.string().optional(),
});

const CleanResultSchema = z.object({
  job_uuid: z.string().uuid(),
  status: z.literal('completed'),
  storage_url: z.string().nullable(),
  duration_seconds: z.number(),
  silence_cuts: z.number().int(),
  review: z.string().optional(),
  diagnostics: CleanDiagnosticsSchema,
});

const MergeResultSchema = z.object({
  job_uuid: z.string().uuid(),
  status: z.literal('completed'),
  storage_url: z.string().nullable(),
  duration_seconds: z.number(),
  diagnostics: z.object({
    source_count: z.number().int(),
    merged: z.boolean(),
    merge_order: z.array(z.string()),
    r2_upload_error: z.string().optional(),
  }),
});

export const JobStatusResponseSchema = z.object({
  job_uuid: z.string(),
  status: z.enum([
    'queued',
    'active',
    'completed',
    'failed',
    'delayed',
    'not_found',
  ]),
  result: z.union([CleanResultSchema, MergeResultSchema]).optional(),
  error: z.string().optional(),
});

export class EnqueueResponseDto extends createZodDto(EnqueueResponseSchema) {}
export class JobStatusResponseDto extends createZodDto(
  JobStatusResponseSchema,
) {}
