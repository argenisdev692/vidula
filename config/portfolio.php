<?php

declare(strict_types=1);

/**
 * Portfolio media upload defaults (direct browser → Cloudflare R2 via
 * StoragePort::temporaryUploadUrl). Cover/video never proxy through the app.
 */
return [
    'presign_expires_seconds' => (int) env('PORTFOLIO_PRESIGN_EXPIRES_SECONDS', 15 * 60),

    'cover_prefix' => 'portfolios/cover',
    'video_prefix' => 'portfolios/video',

    /** Max cover size in bytes (4 MB) — mirrors PortfolioData / FileField. */
    'max_cover_bytes' => 4 * 1024 * 1024,

    /** Max video size in bytes (50 MB) — mirrors PortfolioData / FileField. */
    'max_video_bytes' => 50 * 1024 * 1024,
];
