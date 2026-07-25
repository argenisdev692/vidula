/**
 * Static configuration for the video-export pipeline.
 *
 * This module has NO database tables — all of these are compile-time defaults
 * (a few are overridable via env in `video-export.config.ts`). They mirror the
 * reference FastAPI module's `config.py` so the produced MP4 is byte-spec
 * identical (1920x1080, 30 fps, 794k video + 298k audio).
 */

/** BullMQ job names routed by the processor on the single VIDEO_EXPORT queue. */
export const VIDEO_EXPORT_JOBS = {
  /** merge → silence cut → (optional Whisper cuts) → (optional DSP) → render. */
  CLEAN: 'clean',
  /** merge + HD render only — no cleaning. */
  MERGE: 'merge',
} as const;

export type VideoExportJobName =
  (typeof VIDEO_EXPORT_JOBS)[keyof typeof VIDEO_EXPORT_JOBS];

/**
 * R2 key prefix for browser-uploaded source parts (presigned PUT). These are
 * temporary: the worker deletes them via `deleteSourceParts` once the final
 * MP4/merge is rendered, so only the final result lingers in the bucket.
 */
export const UPLOAD_PARTS_PREFIX = 'video-exports/_parts';

/** Presigned upload URL lifetime — long enough for a slow 2 GB upload to start. */
export const PRESIGN_EXPIRES_SECONDS = 15 * 60;

/**
 * BullMQ retention: keep finished jobs long enough for the client to poll the
 * result, then evict so Redis does not grow unbounded. Completed jobs carry the
 * full result JSON, so they are kept for a day; failures are kept longer for
 * debugging. `age` is in seconds; `count` caps the kept set regardless of age.
 */
export const JOB_RETENTION = {
  removeOnComplete: { age: 24 * 60 * 60, count: 1000 },
  removeOnFail: { age: 7 * 24 * 60 * 60, count: 5000 },
} as const;

/** HD export encode spec (matches the reference module). */
export const RENDER_SPEC = {
  width: 1920,
  height: 1080,
  fps: 30,
  videoCodec: 'libx264',
  preset: 'medium',
  videoBitrate: '794k',
  videoMaxrate: '794k',
  videoBufsize: '1588k',
  pixelFormat: 'yuv420p',
  audioCodec: 'aac',
  audioBitrate: '298k',
  audioSampleRate: 48000,
  audioChannels: 2,
} as const;

/** Merge intermediate is encoded near-lossless so the later trim re-encode keeps quality. */
export const MERGE_INTERMEDIATE = {
  crf: 16,
  preset: 'fast',
  audioBitrate: '320k',
} as const;

/** silencedetect noise floor — anything quieter than this for `threshold` seconds is a silence. */
export const SILENCE_NOISE_DB = '-30dB';

/** Drop any keep/cut segment shorter than this (seconds) — avoids ffmpeg trim artefacts. */
export const MIN_SEGMENT_SECONDS = 0.25;

/** Whisper audio is extracted as low-bitrate mono mp3 to stay under the API file-size cap. */
export const WHISPER_AUDIO = {
  sampleRate: 16000,
  channels: 1,
  bitrate: '32k',
  /** OpenAI hard limit is 25 MB; we guard slightly below it. */
  maxFileBytes: 24 * 1024 * 1024,
} as const;

/** Filler words ("muletillas") cut when `ai_cleaning_enabled` is true. */
export const FILLER_TERMS: readonly string[] = [
  'eh',
  'em',
  'emm',
  'mm',
  'mmm',
  'hmm',
  'este',
  'ehh',
  'uh',
  'umm',
  'pues',
];

/**
 * PAUSA / PAUSA ACÁ bad-take markers. The longest (most specific) variants are
 * matched first so "pausa aca" is not shadowed by the bare "pausa".
 */
export const PAUSE_KEYWORDS: readonly string[] = [
  'PAUSA ACA',
  'PAUSA ACÁ',
  'PAUSA A CA',
  'PAUSA A CÁ',
  'PAUSAACA',
  'PAUSAACÁ',
  'PASA ACA',
  'PASA ACÁ',
  'PAUZA ACA',
  'PAUZA ACÁ',
  'PAUSA',
  'PAUZA',
];

/** Backtracking bounds when removing a PAUSA bad take (see filler.service). */
export const PAUSE_BACKTRACK = {
  /** A gap >= this many seconds marks where the bad take started. */
  silenceThresholdSeconds: 0.4,
  /** Never walk back further than this from the keyword. */
  maxSeconds: 8.0,
} as const;

/** Stutter detection bounds. */
export const STUTTER = {
  maxGapSeconds: 0.4,
  maxTokenChars: 5,
} as const;
