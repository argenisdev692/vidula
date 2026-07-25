import { buildExportUrl, type ExportFormat } from '@/lib/queryParams';
import type { CvQuery } from '../types';

/** Export formats accepted by GET /cvs/export. */
export type CvExportFormat = ExportFormat;

/**
 * Single source of truth for CV request params — consumed by BOTH the
 * server-side DataTable reload AND the export URL (no drift).
 */
export function buildCvQueryParams(query: CvQuery): Record<string, string | number> {
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
    if (query.niche) {
        params.niche = query.niche;
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
export function buildCvExportUrl(query: CvQuery, format: CvExportFormat): string {
    return buildExportUrl('/cvs/export', buildCvQueryParams(query), format);
}
