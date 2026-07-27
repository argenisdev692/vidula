import { useMutation } from '@pinia/colada';
import { apiFetch } from '@/lib/http';

export interface MeetingAvailabilityDay {
  date: string;
  is_open: boolean;
  slots: Array<{ start: string; end: string }>;
  reason: string | null;
}

export interface MeetingAvailabilityResponse {
  data: MeetingAvailabilityDay[];
  meta?: { duration_minutes?: number };
}

/**
 * Non-Inertia JSON fetch for day availability windows
 * (`GET /meetings/availability`). Debouncing stays in the dialog.
 */
export function useMeetingAvailabilityMutation() {
  return useMutation({
    mutation: (input: { from: string; to: string }) =>
      apiFetch<MeetingAvailabilityResponse>(
        'GET',
        `/meetings/availability?from=${encodeURIComponent(input.from)}&to=${encodeURIComponent(input.to)}`,
      ),
  });
}
