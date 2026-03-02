export function formatDateShort(isoString: string | Date | undefined | null): string {
    if (!isoString) return '—';
    const date = typeof isoString === 'string' ? new Date(isoString) : isoString;
    if (isNaN(date.getTime())) return '—';
    
    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    }).format(date);
}
