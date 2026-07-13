import { buildExportUrl, type ExportFormat } from '@/lib/queryParams';
import type { CampaignQuery } from '../types';

/** Export formats accepted by GET /campaigns/export. */
export type CampaignExportFormat = ExportFormat;

/**
 * Single source of truth for the campaign request params — consumed by BOTH
 * the server-side DataTable reload AND the export URL, so the two can never
 * drift. Empty filters are omitted so the query string stays clean. The
 * backend `CampaignFilterData` reads `search`, `status`, `date_from`,
 * `date_to`; pagination reads `page` + `per_page`.
 */
export function buildCampaignQueryParams(query: CampaignQuery): Record<string, string | number> {
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
export function buildCampaignExportUrl(query: CampaignQuery, format: CampaignExportFormat): string {
    return buildExportUrl('/campaigns/export', buildCampaignQueryParams(query), format);
}
