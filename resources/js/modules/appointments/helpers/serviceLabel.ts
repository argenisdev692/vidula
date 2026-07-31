import type { Appointment, AppointmentServiceSummary } from '../types';
import { PROJECT_TYPE_LABEL } from './statusMeta';
import type { ProjectType } from '../types';

/** Prefer catalog service name; fall back to legacy `project_type` slug label. */
export function appointmentServiceLabel(
    appointment: Pick<Appointment, 'service' | 'project_type'>,
): string {
    if (appointment.service?.name) {
        return appointment.service.name;
    }

    if (appointment.project_type) {
        return PROJECT_TYPE_LABEL[appointment.project_type as ProjectType];
    }

    return '—';
}

export function serviceOptionsToSelect(
    options: AppointmentServiceSummary[],
): { label: string; value: string }[] {
    return options.map((option) => ({ label: option.name, value: option.uuid }));
}
