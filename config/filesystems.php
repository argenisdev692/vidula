<?php

declare(strict_types=1);

/**
 * Extra browser origins for R2 CORS (Vite, alternate hosts, etc.).
 * The primary origin is always config('app.url') / APP_URL — do not duplicate it here.
 *
 * @return list<string>
 */
$r2CorsExtraOrigins = static function (): array {
    return array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('R2_CORS_EXTRA_ORIGINS', '')),
    )));
};

/**
 * Full AllowedOrigins list: APP_URL (same source as config('app.url')) + extras.
 *
 * @return list<string>
 */
$r2CorsAllowedOrigins = static function () use ($r2CorsExtraOrigins): array {
    return array_values(array_unique(array_filter([
        rtrim((string) env('APP_URL', 'http://localhost'), '/'),
        ...$r2CorsExtraOrigins(),
    ])));
};

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Cloud Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | The disk used for user/business uploads. Resolved by R2StorageAdapter
    | (Shared\Infrastructure\Storage) — local/public disks are forbidden as the
    | final destination for uploads (BACKEND-PHP §5). Defaults to Cloudflare R2.
    |
    */

    'cloud' => env('FILESYSTEM_CLOUD', 'r2'),

    /*
    |--------------------------------------------------------------------------
    | Cloudflare R2 CORS (browser presigned PUT uploads)
    |--------------------------------------------------------------------------
    |
    | Video-export uploads XHR PUT directly from the browser to signed R2 URLs.
    | Primary origin = config('app.url') (APP_URL). Extras = R2_CORS_EXTRA_ORIGINS.
    | Apply with: ./vendor/bin/sail artisan r2:sync-cors
    |
    */

    'r2_cors' => [
        'allowed_origins' => $r2CorsAllowedOrigins(),
        'extra_origins' => $r2CorsExtraOrigins(),
        'allowed_methods' => ['GET', 'PUT', 'HEAD'],
        'allowed_headers' => ['*'],
        'expose_headers' => ['ETag', 'Content-Type'],
        'max_age_seconds' => 3600,
    ],

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

        // Cloudflare R2 (S3-compatible). Region is always "auto"; presigned
        // temporaryUrl() works over the S3 driver. Objects are private by
        // default — public access is ALWAYS via a signed URL.
        'r2' => [
            'driver' => 's3',
            'key' => env('R2_ACCESS_KEY_ID'),
            'secret' => env('R2_SECRET_ACCESS_KEY'),
            'region' => env('R2_DEFAULT_REGION', 'auto'),
            'bucket' => env('R2_BUCKET'),
            'url' => env('R2_URL'),
            'endpoint' => env('R2_ENDPOINT'),
            'use_path_style_endpoint' => env('R2_USE_PATH_STYLE_ENDPOINT', false),
            'visibility' => 'private',
            'throw' => true,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
