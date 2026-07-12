/** Presentation metadata (label + Volt `<Tag>` severity) for the pipeline enums. */
import type { TagSeverity } from '@/volt/Tag.vue';
import type { ClientType, MeetingStatus, ProjectType, StatusLead } from '../types';

export const STATUS_LEAD_META: Record<StatusLead, { label: string; severity: TagSeverity }> = {
    New: { label: 'New', severity: 'info' },
    Called: { label: 'Called', severity: 'warn' },
    Pending: { label: 'Pending', severity: 'secondary' },
    Declined: { label: 'Declined', severity: 'danger' },
};

export const MEETING_STATUS_META: Record<Exclude<MeetingStatus, null>, { label: string; severity: TagSeverity }> = {
    Confirmed: { label: 'Confirmed', severity: 'success' },
    Rescheduled: { label: 'Rescheduled', severity: 'warn' },
    Cancelled: { label: 'Cancelled', severity: 'danger' },
};

export const CLIENT_TYPE_LABEL: Record<ClientType, string> = {
    individual: 'Individual',
    company: 'Company',
};

export const PROJECT_TYPE_LABEL: Record<ProjectType, string> = {
    new_website: 'New website',
    redesign: 'Redesign',
    ecommerce: 'E-commerce',
    landing_page: 'Landing page',
    maintenance: 'Maintenance',
    other: 'Other',
};
