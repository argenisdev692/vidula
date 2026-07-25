/**
 * Formats an ISO timestamp into a short, locale-aware date (e.g. "Jul 8, 2026").
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
