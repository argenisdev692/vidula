/**
 * Fixed domain-vocabulary options for the appointment form + filters. Labels
 * are derived from {@link ./statusMeta} so the table Tags and the Select
 * options can never drift. Values mirror the backend enums (`ClientType`,
 * `ProjectType`, `StatusLead`, `MeetingStatus`).
 */
import type { SelectOption } from '@/common/form/types';
import type { ClientType, ProjectType } from '../types';
import {
  CLIENT_TYPE_LABEL,
  MEETING_STATUS_META,
  PROJECT_TYPE_LABEL,
  STATUS_LEAD_META,
} from './statusMeta';

function optionsFromRecord<T extends string>(
  record: Record<T, string | { label: string }>,
): SelectOption[] {
  return (Object.entries(record) as Array<[T, string | { label: string }]>).map(
    ([value, entry]) => ({
      value,
      label: typeof entry === 'string' ? entry : entry.label,
    }),
  );
}

export const CLIENT_TYPE_OPTIONS: SelectOption[] = optionsFromRecord(CLIENT_TYPE_LABEL);

export const PROJECT_TYPE_OPTIONS: SelectOption[] = optionsFromRecord(PROJECT_TYPE_LABEL);

export const STATUS_LEAD_OPTIONS: SelectOption[] = optionsFromRecord(STATUS_LEAD_META);

export const MEETING_STATUS_OPTIONS: SelectOption[] = optionsFromRecord(MEETING_STATUS_META);

/** Soft-delete list filter (Active / Suspended). */
export const APPOINTMENT_STATUS_OPTIONS: SelectOption[] = [
  { label: 'Active', value: 'active' },
  { label: 'Suspended', value: 'suspended' },
];

/** Inbox read filter. */
export const APPOINTMENT_READ_OPTIONS: SelectOption[] = [
  { label: 'Read', value: 'read' },
  { label: 'Unread', value: 'unread' },
];

/** Anti-spam filter. */
export const APPOINTMENT_SPAM_OPTIONS: SelectOption[] = [
  { label: 'Legitimate', value: 'ham' },
  { label: 'Spam', value: 'spam' },
];

/** Stable enum values for Zod (DRY with PROJECT_TYPE_OPTIONS / CLIENT_TYPE_OPTIONS). */
export const PROJECT_TYPE_VALUES = PROJECT_TYPE_OPTIONS.map(
  (option) => option.value as ProjectType,
) as [ProjectType, ...ProjectType[]];

export const CLIENT_TYPE_VALUES = CLIENT_TYPE_OPTIONS.map(
  (option) => option.value as ClientType,
) as [ClientType, ...ClientType[]];
