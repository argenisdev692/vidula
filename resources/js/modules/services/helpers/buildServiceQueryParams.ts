import type { ServiceQuery } from '../types';

/**
 * Single source of truth for the service request params, consumed by the
 * server-side DataTable reload. Empty filters are omitted so the query string
 * stays clean. The backend `ServiceFilterData` reads `search`, `status`,
 * `date_from`, `date_to`; pagination reads `page` + `per_page`. Services has
 * no export endpoint (see RolePermissionSeeder::NO_EXPORT_MODULES), so unlike
 * blog-categories there is no companion `buildServiceExportUrl`.
 */
export function buildServiceQueryParams(query: ServiceQuery): Record<string, string | number> {
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
