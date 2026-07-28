/**
 * Turns a raw User-Agent into a short "Browser · OS" label for session and
 * trusted-device lists. Order matters: Edge/Opera UAs also contain Chrome/Safari.
 */
export function deviceLabel(userAgent: string | null | undefined): string {
    if (!userAgent) {
        return 'Unknown device';
    }

    const browser = /Edg\//.test(userAgent)
        ? 'Microsoft Edge'
        : /OPR\/|Opera/.test(userAgent)
          ? 'Opera'
          : /Chrome\//.test(userAgent)
            ? 'Chrome'
            : /Firefox\//.test(userAgent)
              ? 'Firefox'
              : /Safari\//.test(userAgent)
                ? 'Safari'
                : 'Unknown browser';

    const os = /Windows/.test(userAgent)
        ? 'Windows'
        : /Mac OS X|Macintosh/.test(userAgent)
          ? 'macOS'
          : /Android/.test(userAgent)
            ? 'Android'
            : /iPhone|iPad|iPod|iOS/.test(userAgent)
              ? 'iOS'
              : /Linux/.test(userAgent)
                ? 'Linux'
                : '';

    return os ? `${browser} · ${os}` : browser;
}
