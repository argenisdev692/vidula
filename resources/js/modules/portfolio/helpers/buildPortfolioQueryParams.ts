import { buildExportUrl, type ExportFormat } from '@/lib/queryParams';
import type { PortfolioQuery } from '../types';

/** Export formats accepted by GET /portfolios/export. */
export type PortfolioExportFormat = ExportFormat;

/**
 * Single source of truth for portfolio request params — consumed by BOTH the
 * server-side DataTable reload AND the export URL (no drift).
 */
export function buildPortfolioQueryParams(query: PortfolioQuery): Record<string, string | number> {
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
export function buildPortfolioExportUrl(query: PortfolioQuery, format: PortfolioExportFormat): string {
    return buildExportUrl('/portfolios/export', buildPortfolioQueryParams(query), format);
}
