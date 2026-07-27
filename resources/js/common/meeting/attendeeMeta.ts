/**
 * Shared attendee type labels / Tag severities for Meeting UI.
 * Lives in common/ so AttendeePicker (common) and Show (pages) can both use it
 * without crossing the modules → common import boundary the wrong way.
 */
export type MeetingAttendeeType = 'user' | 'lead' | 'contact';

export const ATTENDEE_TYPE_LABEL: Record<MeetingAttendeeType, string> = {
  user: 'User',
  lead: 'Lead',
  contact: 'Contact',
};

export const ATTENDEE_TYPE_SEVERITY: Record<
  MeetingAttendeeType,
  'info' | 'primary' | 'secondary'
> = {
  user: 'info',
  lead: 'primary',
  contact: 'secondary',
};
