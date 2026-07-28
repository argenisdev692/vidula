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
