import { z } from 'zod';

/**
 * Client-side UX validation for the service create/edit form (Zod v4).
 * Mirrors the backend rules in Modules\Services\Application\DTOs\ServiceData
 * (name required ≤255; slug required ≤100 alpha_dash, unique server-side;
 * description nullable ≤500; sort_order nullable non-negative integer); the
 * backend stays authoritative. `sort_order` is kept as a string here since it
 * drives a text input — the form converts it to a number on submit.
 */
export const serviceFormSchema = z.object({
    name: z.string().trim().min(1, 'Name is required.').max(255, 'Name must be 255 characters or fewer.'),
    slug: z
        .string()
        .trim()
        .min(1, 'Slug is required.')
        .max(100, 'Slug must be 100 characters or fewer.')
        .regex(/^[A-Za-z0-9_-]+$/, 'Slug may only contain letters, numbers, dashes and underscores.'),
    description: z.string().trim().max(500, 'Description must be 500 characters or fewer.'),
    is_active: z.boolean(),
    sort_order: z.string().trim().regex(/^\d*$/, 'Sort order must be a whole number.'),
});

export type ServiceFormValues = z.infer<typeof serviceFormSchema>;
