import type { ActivityLogQuery } from '@/modules/activity-log/types';

/**
 * Single source of truth for the activity-log request params — consumed by BOTH
 * the server-side DataTable reload AND the export URL, so the two can never
 * drift. Empty/nullish values are omitted so the query string stays clean.
 */
export function buildActivityLogQueryParams(query: ActivityLogQuery): Record<string, string | number> {
    const params: Record<string, string | number> = {
        page: query.page,
        per_page: query.per_page,
        sort_dir: query.sort_dir,
    };

    const optional: Array<[keyof ActivityLogQuery, string]> = [
        ['search', 'search'],
        ['event', 'event'],
        ['log_name', 'log_name'],
        ['causer_id', 'causer_id'],
        ['date_from', 'date_from'],
        ['date_to', 'date_to'],
    ];

    for (const [key, param] of optional) {
        const value = query[key];
        if (value !== null && value !== undefined && String(value).trim() !== '') {
            params[param] = String(value);
        }
    }

    return params;
}

/** Builds the export download URL for the given format, reusing the same params. */
export function buildActivityLogExportUrl(query: ActivityLogQuery, format: 'csv' | 'xlsx' | 'pdf'): string {
    const params = buildActivityLogQueryParams(query);
    const search = new URLSearchParams({ ...Object.fromEntries(Object.entries(params).map(([k, v]) => [k, String(v)])), format });
    // Page/per_page are irrelevant to a full export — drop them for clarity.
    search.delete('page');
    search.delete('per_page');

    return `/activity-logs/export?${search.toString()}`;
}
