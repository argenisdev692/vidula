import { buildExportUrl, type ExportFormat } from '@/lib/queryParams';
import type { PostQuery } from '../types';

/** Export formats accepted by GET /posts/export. */
export type PostExportFormat = ExportFormat;

/**
 * Single source of truth for the post request params — consumed by BOTH the
 * server-side DataTable reload AND the export URL, so the two can never drift.
 * Empty filters are omitted so the query string stays clean. The backend
 * `PostFilterData` reads `search`, `status`, `date_from`, `date_to`,
 * `category_uuid`, `sort_field`, `sort_order`; pagination reads `page` + `per_page`.
 */
export function buildPostQueryParams(query: PostQuery): Record<string, string | number> {
    const params: Record<string, string | number> = {
        page: query.page,
        per_page: query.per_page,
        sort_field: query.sort_field,
        sort_order: query.sort_order,
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
    if (query.category_uuid) {
        params.category_uuid = query.category_uuid;
    }

    return params;
}

/** Builds the export download URL for the given format, reusing the same filters. */
export function buildPostExportUrl(query: PostQuery, format: PostExportFormat): string {
    return buildExportUrl('/posts/export', buildPostQueryParams(query), format);
}
