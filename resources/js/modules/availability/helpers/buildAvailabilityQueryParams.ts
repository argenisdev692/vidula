import type { AvailabilityExceptionQuery, AvailabilityRuleQuery } from '../types';

/** Export formats accepted by the Availability export controllers. */
export type AvailabilityExportFormat = 'csv' | 'xlsx' | 'pdf';

/**
 * Single source of truth for each entity's request params — consumed by BOTH the
 * server-side DataTable reload AND its export URL, so the two can never drift.
 * Empty filters are omitted so the query string stays clean.
 */
export function buildAvailabilityRuleQueryParams(query: AvailabilityRuleQuery): Record<string, string | number> {
    const params: Record<string, string | number> = {
        page: query.page,
        per_page: query.per_page,
    };

    if (query.day_of_week !== null && query.day_of_week !== undefined) {
        params.day_of_week = query.day_of_week;
    }
    if (query.availability) {
        params.availability = query.availability;
    }
    if (query.status) {
        params.status = query.status;
    }

    return params;
}

export function buildAvailabilityExceptionQueryParams(query: AvailabilityExceptionQuery): Record<string, string | number> {
    const params: Record<string, string | number> = {
        page: query.page,
        per_page: query.per_page,
    };

    if (query.availability) {
        params.availability = query.availability;
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

/**
 * Builds an export download URL for the given base path + filters, reusing the
 * same params object the list reload uses. Pagination is dropped — page size is
 * irrelevant to a full export.
 */
export function buildAvailabilityExportUrl(
    base: string,
    params: Record<string, string | number>,
    format: AvailabilityExportFormat,
): string {
    const search = new URLSearchParams(
        Object.fromEntries(Object.entries(params).map(([k, v]) => [k, String(v)])),
    );
    search.delete('page');
    search.delete('per_page');
    search.set('format', format);

    return `${base}?${search.toString()}`;
}
