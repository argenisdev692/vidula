import type { PortfolioQuery } from '../types';

/**
 * Single source of truth for the portfolio request params, consumed by the
 * server-side DataTable reload. Empty filters are omitted so the query string
 * stays clean. The backend `PortfolioFilterData` reads `search`, `status`,
 * `date_from`, `date_to`; pagination reads `page` + `per_page`. Portfolio has
 * no export endpoint (see RolePermissionSeeder::NO_EXPORT_MODULES), so there is
 * no companion `buildPortfolioExportUrl`.
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
