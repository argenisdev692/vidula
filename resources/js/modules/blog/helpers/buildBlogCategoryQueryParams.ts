import type { BlogCategoryQuery } from '../types';

/** Export formats accepted by GET /blog-categories/export. */
export type BlogCategoryExportFormat = 'csv' | 'xlsx' | 'pdf';

/**
 * Single source of truth for the blog-category request params — consumed by BOTH
 * the server-side DataTable reload AND the export URL, so the two can never
 * drift. Empty filters are omitted so the query string stays clean. The backend
 * `BlogCategoryFilterData` reads `search`, `status`, `date_from`, `date_to`;
 * pagination reads `page` + `per_page`.
 */
export function buildBlogCategoryQueryParams(query: BlogCategoryQuery): Record<string, string | number> {
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
export function buildBlogCategoryExportUrl(query: BlogCategoryQuery, format: BlogCategoryExportFormat): string {
    const params = buildBlogCategoryQueryParams(query);
    const search = new URLSearchParams(
        Object.fromEntries(Object.entries(params).map(([k, v]) => [k, String(v)])),
    );
    // Page size is irrelevant to a full export — drop pagination for clarity.
    search.delete('page');
    search.delete('per_page');
    search.set('format', format);

    return `/blog-categories/export?${search.toString()}`;
}
