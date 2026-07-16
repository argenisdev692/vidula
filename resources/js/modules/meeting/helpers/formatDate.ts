/**
 * Formats an ISO timestamp into a short, locale-aware date (e.g. "Jul 8, 2026").
 * Returns an em dash for null / unparsable input so table cells never render
 * "Invalid Date". Mirrors `modules/appointments/helpers/formatDate`.
 */
export function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return '—';
    }

    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

/** Formats an ISO timestamp into a short date + time (e.g. "Jul 8, 2026, 14:32"). */
export function formatDateTime(value: string | null): string {
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
