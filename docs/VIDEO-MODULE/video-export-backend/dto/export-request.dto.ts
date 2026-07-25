import { z } from 'zod';
import { createZodDto } from 'nestjs-zod';

/**
 * `POST /video-export` — async cleaning job.
 *
 * Pipeline (in the BullMQ worker): merge → silence removal → optional Whisper
 * cleaning (fillers + stutters + PAUSA) → optional ffmpeg audio enhancement →
 * HD render → R2 upload. Only the final MP4 is kept in R2.
 */
export const ExportRequestSchema = z.object({
  /** Caller-supplied UUID; doubles as the BullMQ job id and the R2 path. */
  job_uuid: z.string().uuid(),

  /**
   * Ordered list of source video http(s) URLs (e.g. R2/object storage). Local
   * paths are rejected by the input resolver (URL-only, anti-LFI). When
   * `sort_by_creation_time` is true the array order is ignored and the parts
   * are merged by their recording `creation_time` instead.
   * Bounded to 50 to cap worst-case ffmpeg input width (OWASP API #4).
   */
  video_paths: z.array(z.string().min(1).max(2048)).min(1).max(50),

  /**
   * Minimum silence length (seconds) to cut. Integer 1, 2 or 3 — defaults to 1
   * when the frontend omits it.
   */
  silence_threshold_seconds: z.number().int().min(1).max(3).default(1),

  /**
   * Master switch for the paid OpenAI Whisper call. When false (default) the
   * job only removes silence (free, local ffmpeg). When true it transcribes
   * with word timestamps and additionally removes fillers / stutters / PAUSA
   * bad takes. NOTE: sending `script_path` forces this on, because the Gemini
   * review only makes sense on an already-cleaned video.
   */
  ai_cleaning_enabled: z.boolean().default(false),

  /**
   * ffmpeg DSP audio enhancement (noise removal + studio sound). Free + local,
   * independent of Whisper, on by default.
   */
  audio_enhancement_enabled: z.boolean().default(true),

  /**
   * Merge parts by their `creation_time` metadata instead of array order, so
   * the client never has to rename files to `video_1`, `video_2`, … Falls back
   * to Last-Modified then array order when the metadata is missing.
   */
  sort_by_creation_time: z.boolean().default(true),

  // ── Sub-toggles (only consulted when ai_cleaning_enabled is true) ──────────
  detect_fillers: z.boolean().default(true),
  detect_stutters: z.boolean().default(true),
  detect_pause: z.boolean().default(true),

  /** Whisper transcription language hint. */
  language: z.string().min(2).max(8).default('es'),

  /**
   * Optional script/guion http(s) URL (e.g. R2/object storage). When present, after the fix
   * cleaning completes, Gemini reviews the cleaned video against this script
   * (compliance, suggested cuts, leftover "pausa" fragments) and a PDF report
   * is produced. Providing it forces a Whisper transcription even when
   * `ai_cleaning_enabled` is false (needed to build the review).
   */
  script_path: z.string().min(1).max(2048).optional(),

  /**
   * Script format. Optional — inferred from the extension when omitted
   * (`.pdf` → pdf, otherwise markdown). PDF is read natively by Gemini.
   */
  script_format: z.enum(['markdown', 'pdf']).optional(),
});

export class ExportRequestDto extends createZodDto(ExportRequestSchema) {}
