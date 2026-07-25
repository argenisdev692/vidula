import { EnqueueResponseDto } from '../../../api/models/enqueue-response-dto';
import { JobStatusResponseDto } from '../../../api/models/job-status-response-dto';

export type { EnqueueResponseDto, JobStatusResponseDto };

/** Lifecycle status of a video-export job (mirrors the API union). */
export type JobStatus = JobStatusResponseDto['status'];

/** Successful job payload (narrowed from the API `result` union). */
export type JobResult = NonNullable<JobStatusResponseDto['result']>;

/** Diagnostics block returned on a completed job. */
export type JobDiagnostics = JobResult['diagnostics'];

/** Enqueue outcome (mirrors the API union). */
export type EnqueueStatus = EnqueueResponseDto['status'];

/**
 * Typed request body for a video-export job.
 *
 * The generated OpenAPI body is `Function` (an empty placeholder schema), so we
 * model the payload here based on the diagnostics the backend reports. The
 * feature service casts this to the generated body type when calling the API.
 */
export interface VideoExportRequest {
  /** Ordered list of source video URLs/keys to merge. */
  video_paths: string[];
  /** Unique identifier for this job (generated client-side). */
  job_uuid: string;
  /** Enable silence/filler/stutter AI cleaning (full export only). */
  aiCleaning?: boolean;
  /** Enable audio enhancement. */
  audioEnhance?: boolean;
  /** Generate a script review PDF. */
  scriptReview?: boolean;
  /** Optional script file URL (when scriptReview is enabled). */
  script_path?: string;
  /** Script format (inferred from extension if omitted). */
  script_format?: 'markdown' | 'pdf';
}

/** Per-file state tracked by the dropzone while uploading to R2. */
export interface UploadItem {
  file: File;
  status: 'pending' | 'uploading' | 'done' | 'error';
  progress: number;
  publicUrl?: string;
  error?: string;
}

/** The two enqueue modes exposed by the API. */
export type ExportMode = 'full' | 'merge';

/** Terminal statuses where polling should stop. */
export const TERMINAL_JOB_STATUSES: readonly JobStatus[] = [
  'completed',
  'failed',
  'not_found',
];

export function isTerminalStatus(status: JobStatus): boolean {
  return TERMINAL_JOB_STATUSES.includes(status);
}
