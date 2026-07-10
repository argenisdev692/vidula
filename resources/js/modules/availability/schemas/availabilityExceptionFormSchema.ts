import { z } from 'zod';

/**
 * Client-side UX validation for the date-exception create/edit form (Zod v4).
 * Mirrors {@link \Modules\Availability\Application\DTOs\AvailabilityExceptionData}:
 * a closure (`is_available = false`) needs no hours; a forced-open day
 * (`is_available = true`) MUST carry `start_time`/`end_time` with end after
 * start. The backend stays authoritative — including the "one active exception
 * per date" uniqueness rule, which cannot be checked client-side.
 */
const TIME = /^\d{2}:\d{2}$/;

export const availabilityExceptionFormSchema = z
    .object({
        date: z.string().regex(/^\d{4}-\d{2}-\d{2}$/, 'Date is required.'),
        is_available: z.boolean(),
        start_time: z.string().nullable(),
        end_time: z.string().nullable(),
        reason: z.string().trim().max(255, 'Reason must be 255 characters or fewer.').nullable(),
    })
    .superRefine((data, ctx) => {
        if (!data.is_available) {
            return; // a closure ignores the hours (the handler clears them)
        }

        if (!data.start_time || !TIME.test(data.start_time)) {
            ctx.addIssue({ path: ['start_time'], code: 'custom', message: 'Start time is required for an open day.' });
        }

        if (!data.end_time || !TIME.test(data.end_time)) {
            ctx.addIssue({ path: ['end_time'], code: 'custom', message: 'End time is required for an open day.' });
        } else if (data.start_time && TIME.test(data.start_time) && data.end_time <= data.start_time) {
            ctx.addIssue({ path: ['end_time'], code: 'custom', message: 'End time must be after start time.' });
        }
    });

export type AvailabilityExceptionFormValues = z.infer<typeof availabilityExceptionFormSchema>;
