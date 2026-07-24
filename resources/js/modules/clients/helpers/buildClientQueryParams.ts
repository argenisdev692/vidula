import { buildExportUrl, type ExportFormat } from '@/lib/queryParams';
import type { ClientQuery } from '../types';

/** Export formats accepted by GET /clients/export. */
export type ClientExportFormat = ExportFormat;

/**
 * Single source of truth for client request params — consumed by BOTH the
 * server-side DataTable reload AND the export URL (no drift).
 */
export function buildClientQueryParams(query: ClientQuery): Record<string, string | number> {
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
    if (query.client_status) {
        params.client_status = query.client_status;
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
export function buildClientExportUrl(query: ClientQuery, format: ClientExportFormat): string {
    return buildExportUrl('/clients/export', buildClientQueryParams(query), format);
}
