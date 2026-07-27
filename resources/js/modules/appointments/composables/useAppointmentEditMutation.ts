import { useMutation } from '@pinia/colada';
import { apiFetch } from '@/lib/http';
import type { AppointmentEditData } from '../types';

/**
 * Non-Inertia JSON fetch for the edit dialog payload
 * (`GET /appointments/{uuid}/edit`). Server state via Pinia Colada.
 */
export function useAppointmentEditMutation() {
  return useMutation({
    mutation: (uuid: string) =>
      apiFetch<{ data: AppointmentEditData }>('GET', `/appointments/${uuid}/edit`),
  });
}
