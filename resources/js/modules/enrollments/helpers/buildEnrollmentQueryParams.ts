import type { EnrollmentQuery } from '../types';

export function buildEnrollmentQueryParams(query: EnrollmentQuery): Record<string, string | number> {
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
    if (query.enrollment_status) {
        params.enrollment_status = query.enrollment_status;
    }
    if (query.classroom_uuid) {
        params.classroom_uuid = query.classroom_uuid;
    }
    if (query.student_uuid) {
        params.student_uuid = query.student_uuid;
    }
    if (query.date_from) {
        params.date_from = query.date_from;
    }
    if (query.date_to) {
        params.date_to = query.date_to;
    }

    return params;
}
