/**
 * Formats an ISO timestamp for session / trusted-device meta lines
 * (medium date + short time). Returns an em dash for null / invalid input.
 */
export function formatSessionDate(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return '—';
    }

    return date.toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}
