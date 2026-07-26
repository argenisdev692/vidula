<?php

declare(strict_types=1);

/**
 * Video export pipeline defaults (Nest reference parity).
 * No DB — these are compile-time / env-overridable constants.
 */
return [
    'queue' => env('VIDEO_EXPORT_QUEUE', 'video-export'),

    'upload_parts_prefix' => 'video-exports/_parts',
    'result_prefix' => 'video-exports/results',
    'presign_expires_seconds' => 15 * 60,

    'job_cache_ttl_seconds' => 24 * 60 * 60,
    'job_failed_cache_ttl_seconds' => 7 * 24 * 60 * 60,

    'max_source_videos' => 50,
    'max_source_bytes' => 2 * 1024 * 1024 * 1024,

    'workspace_root' => env('VIDEO_EXPORT_WORKSPACE', storage_path('app/video-export')),

    /*
    | Low-memory encode profile (Railway / small containers).
    | Caps FFmpeg threads and uses ultrafast + pairwise merge so filter_complex
    | never opens all sources at once (avoids SIGKILL / OOM).
    | UI toggle overrides per job; env sets the default when the client omits it.
    */
    'low_memory' => [
        'enabled' => filter_var(env('VIDEO_EXPORT_LOW_MEMORY', true), FILTER_VALIDATE_BOOLEAN),
        'preset' => env('VIDEO_EXPORT_LOW_MEMORY_PRESET', 'ultrafast'),
        'threads' => (int) env('VIDEO_EXPORT_LOW_MEMORY_THREADS', 1),
        'filter_threads' => (int) env('VIDEO_EXPORT_LOW_MEMORY_FILTER_THREADS', 1),
    ],

    'render' => [
        'width' => 1920,
        'height' => 1080,
        'fps' => 30,
        'video_codec' => 'libx264',
        'preset' => 'medium',
        'video_bitrate' => '794k',
        'video_maxrate' => '794k',
        'video_bufsize' => '1588k',
        'pixel_format' => 'yuv420p',
        'audio_codec' => 'aac',
        'audio_bitrate' => '298k',
        'audio_sample_rate' => 48000,
        'audio_channels' => 2,
    ],

    'merge_intermediate' => [
        'crf' => 16,
        'preset' => 'fast',
        'audio_bitrate' => '320k',
    ],

    'silence_noise_db' => '-30dB',
    'min_segment_seconds' => 0.25,

    'whisper' => [
        'sample_rate' => 16000,
        'channels' => 1,
        'bitrate' => '32k',
        'max_file_bytes' => 24 * 1024 * 1024,
        'model' => env('OPENAI_WHISPER_MODEL', 'whisper-1'),
    ],

    'filler_terms' => [
        'eh', 'em', 'emm', 'mm', 'mmm', 'hmm', 'este', 'ehh', 'uh', 'umm', 'pues',
    ],

    'pause_keywords' => [
        'PAUSA ACA', 'PAUSA ACÁ', 'PAUSA A CA', 'PAUSA A CÁ',
        'PAUSAACA', 'PAUSAACÁ', 'PASA ACA', 'PASA ACÁ',
        'PAUZA ACA', 'PAUZA ACÁ', 'PAUSA', 'PAUZA',
    ],

    'pause_backtrack' => [
        'silence_threshold_seconds' => 0.4,
        'max_seconds' => 8.0,
    ],

    'stutter' => [
        'max_gap_seconds' => 0.4,
        'max_token_chars' => 5,
    ],

    /*
    | AI background-noise denoise (audio_enhance_mode=ai).
    | Fillers still use Whisper + cut logic — this is waveform denoise only.
    | driver=arnndn → local FFmpeg RNN model (.rnnn).
    | driver=http   → external Audio Cleaner API (https URL only).
    */
    'ai_denoise' => [
        'driver' => env('VIDEO_EXPORT_AI_DENOISE_DRIVER', 'arnndn'),
        'arnndn_model' => env('VIDEO_EXPORT_ARNNDN_MODEL', ''),
        'arnndn_mix' => (float) env('VIDEO_EXPORT_ARNNDN_MIX', 0.8),
        'http_url' => env('VIDEO_EXPORT_AI_DENOISE_URL', ''),
        'http_token' => env('VIDEO_EXPORT_AI_DENOISE_TOKEN', ''),
        'http_timeout' => (int) env('VIDEO_EXPORT_AI_DENOISE_TIMEOUT', 600),
    ],
];
