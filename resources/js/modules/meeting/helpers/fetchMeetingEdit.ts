import { apiFetch } from '@/lib/http';
import type { MeetingEditData } from '../types';

/**
 * Imperative GET for the edit dialog payload (`/meetings/{uuid}/edit`).
 * Plain fetch — Colada `useMutation` is reserved for writes (Pinia Colada docs).
 */
export function fetchMeetingEdit(uuid: string): Promise<{ data: MeetingEditData }> {
    return apiFetch<{ data: MeetingEditData }>('GET', `/meetings/${uuid}/edit`);
}
