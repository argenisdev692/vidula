<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Cloudflare R2 CSP origins
|--------------------------------------------------------------------------
|
| Video-export uploads XHR PUT directly to signed R2 URLs
| (https://{bucket}.{account}.r2.cloudflarestorage.com). Profile photos and
| other private objects also load from those hosts. Custom domains from
| R2_* env vars are parsed into scheme://host origins.
|
*/

$r2OriginFromUrl = static function (string $url): ?string {
    $parts = parse_url($url);
    if (! is_string($parts['scheme'] ?? null) || ! is_string($parts['host'] ?? null)) {
        return null;
    }

    return $parts['scheme'].'://'.$parts['host'];
};

/** @var list<string> $r2ObjectOrigins */
$r2ObjectOrigins = array_values(array_unique(array_filter([
    'https://*.r2.cloudflarestorage.com',
    $r2OriginFromUrl((string) env('R2_URL', '')),
    $r2OriginFromUrl((string) env('R2_ENDPOINT', '')),
    $r2OriginFromUrl((string) env('R2_PUBLIC_BASE_URL', '')),
])));

return [

    /*
    |--------------------------------------------------------------------------
    | Account Lockout (Brute-Force Protection)
    |--------------------------------------------------------------------------
    |
    | After `max_attempts` failed sign-ins for an IP + email pair, the account
    | is locked for `decay_minutes`. Enforced by CacheAccountLockoutAdapter
    | (Redis-backed) on top of Fortify's per-request throttling.
    |
    */

    'lockout' => [
        'max_attempts' => (int) env('AUTH_LOCKOUT_ATTEMPTS', 10),
        'decay_minutes' => (int) env('AUTH_LOCKOUT_MINUTES', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Policy
    |--------------------------------------------------------------------------
    |
    | `expiry_months` forces a password change after N months (EnsurePasswordNotExpired).
    | Set to 0 to disable expiry. `history` is the number of previous hashes that
    | may not be reused (PasswordHistory).
    |
    */

    'password' => [
        'expiry_months' => (int) env('AUTH_PASSWORD_EXPIRY_MONTHS', 3),
        'history' => (int) env('AUTH_PASSWORD_HISTORY', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Mandatory Two-Factor Authentication
    |--------------------------------------------------------------------------
    |
    | When `mandatory` is true, EnsureTwoFactorEnabled redirects privileged
    | roles listed in `mandatory_roles` to the 2FA setup page until they have
    | confirmed TOTP (prompt §3). Tests flip this off via AUTH_MANDATORY_2FA
    | so the suite does not need every admin fixture to enroll 2FA.
    |
    */

    'two_factor' => [
        'mandatory' => (bool) env('AUTH_MANDATORY_2FA', true),
        'mandatory_roles' => ['SUPER_ADMIN', 'ADMIN'],
    ],

    /*
    |--------------------------------------------------------------------------
    | One-Time Passwords (6-digit codes)
    |--------------------------------------------------------------------------
    |
    | Per-purpose expiry: passwordless LOGIN codes are short-lived, while
    | activation (email verification) and password-reset codes get a longer
    | window because they arrive by email and interrupt the user's flow. Both
    | are consumed through spatie/laravel-one-time-passwords; the login value is
    | the package default, the email value is passed per-call via
    | sendOneTimePassword($minutes). Length + attempt limits mirror the package
    | config so there is a single env-driven source.
    |
    */

    'otp' => [
        'login_minutes' => (int) env('AUTH_OTP_EXPIRES_MINUTES', 2),
        'email_minutes' => (int) env('AUTH_OTP_EMAIL_MINUTES', 30),
        'length' => (int) env('AUTH_OTP_LENGTH', 6),
        'max_attempts' => (int) env('AUTH_OTP_MAX_ATTEMPTS', 3),
        'resend_interval_seconds' => (int) env('AUTH_OTP_RESEND_INTERVAL_SECONDS', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Security Headers + Content-Security-Policy
    |--------------------------------------------------------------------------
    |
    | Emitted by the SecurityHeaders middleware (Shared\Infrastructure\Http).
    | The CSP is nonce-based: the per-request nonce is bound to Vite so every
    | asset tag rendered by app.blade.php inherits it, keeping `script-src`
    | free of `'unsafe-inline'`. Runtime inline style attributes (React style
    | props, Framer Motion transforms) are allowed via `style-src-attr` only,
    | never by weakening the main `style-src`.
    |
    */

    'headers' => [
        'permissions_policy' => env(
            'SECURITY_PERMISSIONS_POLICY',
            'camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()',
        ),

        'hsts' => [
            'enabled' => (bool) env('SECURITY_HSTS_ENABLED', true),
            'max_age' => (int) env('SECURITY_HSTS_MAX_AGE', 31536000),
            'include_subdomains' => (bool) env('SECURITY_HSTS_INCLUDE_SUBDOMAINS', true),
            'preload' => (bool) env('SECURITY_HSTS_PRELOAD', false),
        ],

        'csp' => [
            // Extra origins appended to the base `'self'` allowlist.
            // Google Maps: bootstrap + JS API script host.
            'script_src' => ['https://maps.googleapis.com'],
            // Google Maps tiles + R2 signed object URLs (profile photos, etc.).
            'img_src' => array_values(array_unique(array_filter([
                'data:',
                'blob:',
                'https://maps.gstatic.com',
                'https://maps.googleapis.com',
                ...$r2ObjectOrigins,
                ...explode(',', (string) env('SECURITY_CSP_IMG_SRC', '')),
            ]))),
            'font_src' => ['data:'],
            // Google Maps Places XHR + R2 presigned PUT/GET (video-export uploads
            // use XHR PUT directly to the signed URL) + optional env extras
            // (e.g. Reverb websocket endpoint).
            'connect_src' => array_values(array_unique(array_filter([
                'https://maps.googleapis.com',
                'https://places.googleapis.com',
                ...$r2ObjectOrigins,
                ...explode(',', (string) env('SECURITY_CSP_CONNECT_SRC', '')),
            ]))),
        ],
    ],

];
