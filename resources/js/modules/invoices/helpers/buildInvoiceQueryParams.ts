import { buildExportUrl, type ExportFormat } from '@/lib/queryParams';
import type { InvoiceQuery } from '../types';

/** Export formats accepted by GET /invoices/export. */
export type InvoiceExportFormat = ExportFormat;

/**
 * Single source of truth for invoice request params — consumed by BOTH the
 * server-side DataTable reload AND the export URL (no drift).
 */
export function buildInvoiceQueryParams(query: InvoiceQuery): Record<string, string | number> {
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
    if (query.year) {
        params.year = query.year;
    }
    if (query.client_uuid) {
        params.client_uuid = query.client_uuid;
    }

    return params;
}

/** Builds the export download URL for the given format, reusing the same filters. */
export function buildInvoiceExportUrl(query: InvoiceQuery, format: InvoiceExportFormat): string {
    return buildExportUrl('/invoices/export', buildInvoiceQueryParams(query), format);
}
