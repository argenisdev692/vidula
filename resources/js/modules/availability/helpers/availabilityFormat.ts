import type { SelectOption } from '@/common/form/types';

/**
 * Display helpers shared by the Availability tables, detail views and forms.
 * Weekday indices are Carbon-aligned (0 = Sunday … 6 = Saturday) to match the
 * backend `AvailabilityRuleExportTransformer::DAYS` mapping — the single source
 * of truth for day labels across CSV / Excel / PDF and the UI.
 */
export const DAY_LABELS = [
    'Sunday',
    'Monday',
    'Tuesday',
    'Wednesday',
    'Thursday',
    'Friday',
    'Saturday',
] as const;

/** `{ label, value }` options (value is the stringified index) for a day select. */
export const DAY_OPTIONS: SelectOption[] = DAY_LABELS.map((label, index) => ({
    label,
    value: String(index),
}));

/** Human weekday name for a Carbon index; an em dash for anything out of range. */
export function dayLabel(day: number | null): string {
    return day !== null && day >= 0 && day <= 6 ? DAY_LABELS[day] : '—';
}

/** Trims a `HH:MM:SS` (or `HH:MM`) time to `HH:MM`; em dash for null / empty. */
export function formatTime(value: string | null): string {
    return value ? value.slice(0, 5) : '—';
}

/**
 * Formats an ISO date/timestamp into a short, locale-aware date
 * (e.g. "Jul 8, 2026"). Returns an em dash for null / unparsable input so cells
 * never render "Invalid Date".
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
