export type ExportMode = 'merge' | 'clean' | 'ai';

export type AudioEnhanceMode = 'off' | 'dsp' | 'ai';

export type AiProvider = 'openai' | 'anthropic' | 'gemini';

export type JobStatus =
    | 'queued'
    | 'active'
    | 'completed'
    | 'failed'
    | 'not_found'
    | 'delayed';

export interface UploadItem {
    id: string;
    file: File;
    status: 'pending' | 'uploading' | 'done' | 'error';
    progress: number;
    publicUrl?: string;
    error?: string;
}

export interface EnqueueResponse {
    job_uuid: string;
    status: 'queued' | 'duplicate';
}

export interface JobDiagnostics {
    source_count?: number;
    merged?: boolean;
    silence_cuts?: number;
    filler_cuts?: number;
    stutter_cuts?: number;
    pause_cuts?: number;
    keep_segments?: number;
    ai_cleaning_enabled?: boolean;
    ai_provider?: string;
    audio_enhanced?: boolean;
    audio_enhance_mode?: AudioEnhanceMode;
    script_reviewed?: boolean;
    leftover_pause_fragments?: number;
    review_error?: string | null;
    original_duration_seconds?: number;
    mode?: string;
    low_memory?: boolean;
}

export interface JobResult {
    job_uuid: string;
    status: 'completed';
    storage_url: string | null;
    duration_seconds: number;
    silence_cuts?: number;
    review?: string;
    diagnostics: JobDiagnostics;
}

export interface JobStatusResponse {
    job_uuid: string;
    status: JobStatus;
    result?: JobResult | null;
    error?: string | null;
}

export const TERMINAL_JOB_STATUSES: readonly JobStatus[] = [
    'completed',
    'failed',
    'not_found',
];

export function isTerminalStatus(status: JobStatus): boolean {
    return TERMINAL_JOB_STATUSES.includes(status);
}
