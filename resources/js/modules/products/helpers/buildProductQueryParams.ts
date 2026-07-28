import { buildExportUrl, type ExportFormat } from '@/lib/queryParams';
import type { ProductQuery } from '../types';

/** Export formats accepted by GET /products/export. */
export type ProductExportFormat = ExportFormat;

/**
 * Single source of truth for product request params — consumed by BOTH the
 * server-side DataTable reload AND the export URL (no drift).
 */
export function buildProductQueryParams(query: ProductQuery): Record<string, string | number> {
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
    if (query.product_status) {
        params.product_status = query.product_status;
    }
    if (query.type) {
        params.type = query.type;
    }
    if (query.client_uuid) {
        params.client_uuid = query.client_uuid;
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
export function buildProductExportUrl(query: ProductQuery, format: ProductExportFormat): string {
    return buildExportUrl('/products/export', buildProductQueryParams(query), format);
}
