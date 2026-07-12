/**
 * Fixed domain-vocabulary options for the appointment form + filters. These
 * mirror the backend enums (`ClientType`, `ProjectType`, `StatusLead`,
 * `MeetingStatus`) — stable values that never need a server round-trip, so
 * they are hardcoded here rather than passed as Inertia props (mirrors
 * `modules/profile/helpers/genderOptions`).
 */
import type { SelectOption } from '@/common/form/types';

export const CLIENT_TYPE_OPTIONS: SelectOption[] = [
    { label: 'Individual', value: 'individual' },
    { label: 'Company', value: 'company' },
];

export const PROJECT_TYPE_OPTIONS: SelectOption[] = [
    { label: 'New website', value: 'new_website' },
    { label: 'Redesign', value: 'redesign' },
    { label: 'E-commerce', value: 'ecommerce' },
    { label: 'Landing page', value: 'landing_page' },
    { label: 'Maintenance', value: 'maintenance' },
    { label: 'Other', value: 'other' },
];

export const STATUS_LEAD_OPTIONS: SelectOption[] = [
    { label: 'New', value: 'New' },
    { label: 'Called', value: 'Called' },
    { label: 'Pending', value: 'Pending' },
    { label: 'Declined', value: 'Declined' },
];

export const MEETING_STATUS_OPTIONS: SelectOption[] = [
    { label: 'Confirmed', value: 'Confirmed' },
    { label: 'Rescheduled', value: 'Rescheduled' },
    { label: 'Cancelled', value: 'Cancelled' },
];
