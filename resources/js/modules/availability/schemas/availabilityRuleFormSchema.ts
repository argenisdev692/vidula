import { z } from 'zod';

/**
 * Client-side UX validation for the weekly-rule create/edit form (Zod v4).
 * Mirrors {@link \Modules\Availability\Application\DTOs\AvailabilityRuleData}
 * (day 0–6, `H:i` times, end strictly after start); the backend stays
 * authoritative — including the cross-row "no overlapping available slot" rule,
 * which cannot be checked client-side.
 *
 * `day_of_week` is carried as a string (the Select value) and coerced to an int
 * by the form's `transform()` before submit.
 */
export const availabilityRuleFormSchema = z
    .object({
        day_of_week: z.string().min(1, 'Day is required.'),
        start_time: z.string().regex(/^\d{2}:\d{2}$/, 'Start time is required.'),
        end_time: z.string().regex(/^\d{2}:\d{2}$/, 'End time is required.'),
        is_available: z.boolean(),
    })
    .refine((data) => data.end_time > data.start_time, {
        path: ['end_time'],
        message: 'End time must be after start time.',
    });

export type AvailabilityRuleFormValues = z.infer<typeof availabilityRuleFormSchema>;
