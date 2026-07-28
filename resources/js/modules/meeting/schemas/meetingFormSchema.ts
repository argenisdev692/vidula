import { z } from 'zod';

/**
 * Client-side UX validation for the meeting create/edit form (Zod v4).
 * Only `starts_at` is collected — `ends_at` is derived server-side from
 * `config('meeting.duration_minutes')`.
 */
export const meetingFormSchema = z.object({
    title: z.string().trim().min(1, 'Title is required.').max(255, 'Title must be 255 characters or fewer.'),
    description: z.string().trim().max(5000, 'Description must be 5000 characters or fewer.'),
    starts_at: z.string().min(1, 'A date and time is required.'),
    attendees: z
        .array(
            z.object({
                type: z.enum(['user', 'lead', 'contact']),
                uuid: z.string().uuid('Attendee uuid must be a valid UUID.'),
            }),
        )
        .max(100, 'A meeting can have at most 100 attendees.'),
});

export type MeetingFormValues = z.infer<typeof meetingFormSchema>;

/** Default meeting length mirrored from `config/meeting.php` for UI copy. */
export const MEETING_DURATION_MINUTES = 30;
