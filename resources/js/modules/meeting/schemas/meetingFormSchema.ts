import { z } from 'zod';

/**
 * Client-side UX validation for the meeting create/edit form (Zod v4). Mirrors
 * the backend rules in `Modules\Meeting\Application\DTOs\CreateMeetingData` /
 * `UpdateMeetingData` (title required ≤255, description nullable ≤5000,
 * ends_at after starts_at); the backend stays authoritative.
 */
export const meetingFormSchema = z
    .object({
        title: z.string().trim().min(1, 'Title is required.').max(255, 'Title must be 255 characters or fewer.'),
        description: z.string().trim().max(5000, 'Description must be 5000 characters or fewer.'),
        starts_at: z.string().min(1, 'A start date and time is required.'),
        ends_at: z.string().min(1, 'An end date and time is required.'),
        attendees: z.array(z.object({ type: z.string(), uuid: z.string() })).max(100, 'A meeting can have at most 100 attendees.'),
    })
    .refine((data) => !data.starts_at || !data.ends_at || new Date(data.ends_at) > new Date(data.starts_at), {
        message: 'End time must be after the start time.',
        path: ['ends_at'],
    });

export type MeetingFormValues = z.infer<typeof meetingFormSchema>;
