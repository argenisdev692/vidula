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
];
