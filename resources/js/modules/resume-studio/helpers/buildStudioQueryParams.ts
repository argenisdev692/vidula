import { buildExportUrl, type ExportFormat } from '@/lib/queryParams';
import type { StudioQuery } from '../types';

export { formatDate, formatDateShort } from '@/modules/cvs/helpers/formatDate';

/** Export formats accepted by GET /resume-studio/export (job matches). */
export type StudioExportFormat = ExportFormat;

/**
 * Single source of truth for Resume Studio list request params — consumed by the
 * server-side DataTable reload AND the job-match export URL (no drift).
 */
export function buildStudioQueryParams(query: StudioQuery): Record<string, string | number> {
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
    if (query.mode) {
        params.mode = query.mode;
    }
    if (query.date_from) {
        params.date_from = query.date_from;
    }
    if (query.date_to) {
        params.date_to = query.date_to;
    }
    if (query.run_uuid) {
        params.run_uuid = query.run_uuid;
    }

    return params;
}

/** Builds the job-match export download URL, reusing the same filters. */
export function buildStudioExportUrl(query: StudioQuery, format: StudioExportFormat): string {
    return buildExportUrl('/resume-studio/export', buildStudioQueryParams(query), format);
}
