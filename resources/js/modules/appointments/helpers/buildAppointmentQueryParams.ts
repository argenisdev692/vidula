import { buildExportUrl, type ExportFormat } from '@/lib/queryParams';
import type { AppointmentQuery } from '../types';

/**
 * Single source of truth for the appointment request params — consumed by BOTH
 * the server-side DataTable reload AND the export URL, so the two can never
 * drift. Empty filters are omitted so the query string stays clean. Mirrors the
 * backend `AppointmentFilterData` keys.
 */
export function buildAppointmentQueryParams(query: AppointmentQuery): Record<string, string | number> {
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
    if (query.status_lead) {
        params.status_lead = query.status_lead;
    }
    if (query.meeting_status) {
        params.meeting_status = query.meeting_status;
    }
    if (query.client_type) {
        params.client_type = query.client_type;
    }
    if (query.service_uuid) {
        params.service_uuid = query.service_uuid;
    }
    if (query.read) {
        params.read = query.read;
    }
    if (query.spam) {
        params.spam = query.spam;
    }
    if (query.date_from) {
        params.date_from = query.date_from;
    }
    if (query.date_to) {
        params.date_to = query.date_to;
    }
    if (query.scheduled_from) {
        params.scheduled_from = query.scheduled_from;
    }
    if (query.scheduled_to) {
        params.scheduled_to = query.scheduled_to;
    }

    return params;
}

/** Builds the export download URL for the given format, reusing the same filters. */
export function buildAppointmentExportUrl(query: AppointmentQuery, format: ExportFormat): string {
    return buildExportUrl('/appointments/export', buildAppointmentQueryParams(query), format);
}
