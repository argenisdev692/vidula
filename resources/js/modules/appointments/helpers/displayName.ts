/**
 * Display helpers for appointment leads — single source for name formatting
 * used by Index confirmations, DataTable cells, Show title, and Edit page.
 */
import type { Appointment, AppointmentDetail, AppointmentEditData } from '../types';

type NamedLead = Pick<Appointment | AppointmentDetail | AppointmentEditData, 'first_name' | 'last_name'> & {
  email?: string;
};

export function appointmentDisplayName(lead: NamedLead, fallback = '—'): string {
  const name = `${lead.first_name} ${lead.last_name}`.trim();

  if (name !== '') {
    return name;
  }

  if (lead.email) {
    return lead.email;
  }

  return fallback;
}
