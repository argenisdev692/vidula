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
 * Imperative GET for day availability windows (`/meetings/availability`).
 * Used on demand when the form date changes — not a Colada mutation (reads
 * belong to queries / plain fetch; Colada `useMutation` is for writes).
 */
export function fetchMeetingAvailability(input: {
    from: string;
    to: string;
}): Promise<MeetingAvailabilityResponse> {
    return apiFetch<MeetingAvailabilityResponse>(
        'GET',
        `/meetings/availability?from=${encodeURIComponent(input.from)}&to=${encodeURIComponent(input.to)}`,
    );
}
