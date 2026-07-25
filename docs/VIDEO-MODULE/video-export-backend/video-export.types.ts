/**
 * Shared types for the video-export pipeline. No DB entities exist for this
 * module — job state lives in BullMQ/Redis and these are the in-memory shapes
 * passed between the processor and its pipeline services, plus the HTTP
 * response contracts.
 */

/** Half-open time interval `[start, end)` in seconds. */
export interface TimeRange {
  start: number;
  end: number;
}

/** A single source video after download / path resolution. */
export interface ResolvedInput {
  /** Original reference from the request (URL or path). */
  reference: string;
  /** Absolute local path to the readable file. */
  localPath: string;
  /** True when we downloaded it to scratch (and must clean it up). */
  downloaded: boolean;
  /** Sort key used for creation-time ordering, if resolvable. */
  orderKey: number | null;
}

/** One transcribed word with timestamps (from OpenAI Whisper verbose_json). */
export interface WordTimestamp {
  text: string;
  start: number;
  end: number;
}

// ── BullMQ job payloads ──────────────────────────────────────────────────────

export interface CleanJobData {
  jobUuid: string;
  videoPaths: string[];
  silenceThresholdSeconds: number;
  aiCleaningEnabled: boolean;
  audioEnhancementEnabled: boolean;
  sortByCreationTime: boolean;
  detectFillers: boolean;
  detectStutters: boolean;
  detectPause: boolean;
  language: string;
  /** Optional script for the Gemini review (http(s) URL — local paths rejected). */
  scriptPath?: string;
  /** 'markdown' | 'pdf'; inferred from the extension when omitted. */
  scriptFormat?: 'markdown' | 'pdf';
  /** Actor user id (for audit / logging). */
  userId: string;
}

export interface MergeJobData {
  jobUuid: string;
  videoPaths: string[];
  sortByCreationTime: boolean;
  userId: string;
}

// ── Job results (returned by the processor, stored as job.returnvalue) ────────

export interface CleanResult {
  job_uuid: string;
  status: 'completed';
  storage_url: string | null;
  duration_seconds: number;
  silence_cuts: number;
  /** Gemini script-review report (markdown) — present only when a script was sent. */
  review?: string;
  diagnostics: {
    source_count: number;
    merged: boolean;
    merge_order: string[];
    original_duration_seconds: number;
    silence_cuts: number;
    filler_cuts: number;
    stutter_cuts: number;
    pause_cuts: number;
    keep_segments: number;
    ai_cleaning_enabled: boolean;
    audio_enhanced: boolean;
    script_reviewed: boolean;
    leftover_pause_fragments?: number;
    review_pdf_url?: string | null;
    review_error?: string;
    r2_upload_error?: string;
  };
}

export interface MergeResult {
  job_uuid: string;
  status: 'completed';
  storage_url: string | null;
  duration_seconds: number;
  diagnostics: {
    source_count: number;
    merged: boolean;
    merge_order: string[];
    r2_upload_error?: string;
  };
}

// ── HTTP responses ────────────────────────────────────────────────────────────

/** 202 body returned by both POST endpoints. */
export interface EnqueueResponse {
  job_uuid: string;
  status: 'queued' | 'duplicate';
  detail?: string;
}

/** POST /video-export/uploads/presign — presigned direct-to-R2 upload slot. */
export interface PresignUploadResult {
  upload_url: string;
  public_url: string;
  key: string;
  expires_in_seconds: number;
}

/** GET /video-export/jobs/:job_uuid */
export interface JobStatusResponse {
  job_uuid: string;
  /** BullMQ state mapped to a stable contract. */
  status:
    | 'queued'
    | 'active'
    | 'completed'
    | 'failed'
    | 'delayed'
    | 'not_found';
  /** Present only when status === 'completed'. */
  result?: CleanResult | MergeResult;
  /** Present only when status === 'failed'. */
  error?: string;
}
