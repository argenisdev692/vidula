import type { UserQuery } from '../types';

/** Export formats accepted by GET /users/export. */
export type UserExportFormat = 'csv' | 'xlsx' | 'pdf';

/**
 * Single source of truth for the user request params — consumed by BOTH the
 * server-side DataTable reload AND the export URL, so the two can never drift.
 * Empty filters are omitted so the query string stays clean. The backend
 * `UserFilterData` reads `search`, `status`, `date_from`, `date_to`; pagination
 * reads `page` + `per_page`.
 */
export function buildUserQueryParams(query: UserQuery): Record<string, string | number> {
    const params: Record<string, string | number> = {
        page: query.page,
        per_page: query.per_page,
    };

    if (query.search) {
        params.search = query.search;
    }
    if (query.status) {
        params.status = query.status;
    }
    if (query.date_from) {
        params.date_from = query.date_from;
    }
    if (query.date_to) {
        params.date_to = query.date_to;
    }

    return params;
}

/** Builds the export download URL for the given format, reusing the same filters. */
export function buildUserExportUrl(query: UserQuery, format: UserExportFormat): string {
    const params = buildUserQueryParams(query);
    const search = new URLSearchParams(
        Object.fromEntries(Object.entries(params).map(([k, v]) => [k, String(v)])),
    );
    // Page size is irrelevant to a full export — drop pagination for clarity.
    search.delete('page');
    search.delete('per_page');
    search.set('format', format);

    return `/users/export?${search.toString()}`;
}
