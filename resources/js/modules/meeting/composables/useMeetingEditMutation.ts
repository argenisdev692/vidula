import { useMutation } from '@pinia/colada';
import { apiFetch } from '@/lib/http';
import type { MeetingEditData } from '../types';

/**
 * Non-Inertia JSON fetch for the edit dialog payload
 * (`GET /meetings/{uuid}/edit`). Server state via Pinia Colada.
 */
export function useMeetingEditMutation() {
  return useMutation({
    mutation: (uuid: string) =>
      apiFetch<{ data: MeetingEditData }>('GET', `/meetings/${uuid}/edit`),
  });
}
