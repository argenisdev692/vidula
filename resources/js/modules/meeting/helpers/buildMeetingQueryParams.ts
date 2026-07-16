import { buildExportUrl, type ExportFormat } from '@/lib/queryParams';
import type { MeetingQuery } from '../types';

/**
 * Single source of truth for the meeting request params — consumed by BOTH
 * the server-side DataTable reload AND the export URL, so the two can never
 * drift. Mirrors `buildAppointmentQueryParams`.
 */
export function buildMeetingQueryParams(query: MeetingQuery): Record<string, string | number> {
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
    if (query.meeting_status) {
        params.meeting_status = query.meeting_status;
    }
    if (query.date_from) {
        params.date_from = query.date_from;
    }
    if (query.date_to) {
        params.date_to = query.date_to;
    }
    if (query.starts_from) {
        params.starts_from = query.starts_from;
    }
    if (query.starts_to) {
        params.starts_to = query.starts_to;
    }

    return params;
}

/** Builds the export download URL for the given format, reusing the same filters. */
export function buildMeetingExportUrl(query: MeetingQuery, format: ExportFormat): string {
    return buildExportUrl('/meetings/export', buildMeetingQueryParams(query), format);
}
