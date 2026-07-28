import { buildExportUrl, type ExportFormat } from '@/lib/queryParams';
import type { StudentQuery } from '../types';

/** Export formats accepted by GET /students/export. */
export type StudentExportFormat = ExportFormat;

/**
 * Single source of truth for student request params — consumed by BOTH the
 * server-side DataTable reload AND the export URL (no drift).
 */
export function buildStudentQueryParams(query: StudentQuery): Record<string, string | number> {
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
    if (query.student_status) {
        params.student_status = query.student_status;
    }
    if (query.active !== null) {
        params.active = query.active ? 1 : 0;
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
export function buildStudentExportUrl(query: StudentQuery, format: StudentExportFormat): string {
    return buildExportUrl('/students/export', buildStudentQueryParams(query), format);
}
