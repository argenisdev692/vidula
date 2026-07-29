<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Support;

/**
 * Same-origin intended-URL gate shared by Fortify post-auth redirects and the
 * idle-logout flow (OWASP A01 — open redirect). Only absolute local paths are
 * accepted; external hosts (e.g. accounts.google.com / gmail.com) are dropped.
 */
final class SafeIntended
{
    /** @var list<string> */
    private const array DENIED_PREFIXES = [
        '/login',
        '/register',
        '/forgot-password',
        '/reset-password',
        '/logout',
        '/auth',
        '/two-factor',
        '/user/confirm-password',
    ];

    public static function normalize(?string $intended): ?string
    {
        if ($intended === null || $intended === '' || ! str_starts_with($intended, '/')) {
            return null;
        }

        if (str_starts_with($intended, '//') || str_contains($intended, '\\')) {
            return null;
        }

        // Reject absolute URLs that slipped into the session as a path-like string.
        if (str_contains($intended, '://')) {
            return null;
        }

        $path = $intended
            |> (fn (string $s): string => explode('#', $s, 2)[0])
            |> (fn (string $s): string => explode('?', $s, 2)[0]);

        foreach (self::DENIED_PREFIXES as $denied) {
            if (str_starts_with($path, $denied)) {
                return null;
            }
        }

        return $intended;
    }

    public static function pull(?string $fallback = '/dashboard'): string
    {
        $intended = session()->pull('url.intended');

        return self::normalize(is_string($intended) ? $intended : null) ?? $fallback;
    }
}
