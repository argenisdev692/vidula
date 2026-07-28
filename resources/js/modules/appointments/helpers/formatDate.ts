/**
 * Formats an ISO timestamp into a short, locale-aware date (e.g. "Jul 8, 2026").
 * Returns an em dash for null / unparsable input so table cells never render
 * "Invalid Date". Alias {@see formatDateShort} matches FRONTEND §7 naming.
 */
export function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return '—';
    }

    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

/** §7 DataTable date columns — same short format as {@see formatDate}. */
export function formatDateShort(value: string | null | undefined): string {
    return formatDate(value);
}

/**
 * Formats an ISO timestamp into a short date + time (e.g. "Jul 8, 2026, 14:32").
 * Used wherever the exact meeting/submission time matters.
 */
export function formatDateTime(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return '—';
    }

    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
