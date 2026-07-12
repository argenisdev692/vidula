import { z } from 'zod';

/**
 * Client-side UX validation for the post create/edit form (Zod v4). Mirrors
 * the backend rules in Modules\Post\Application\DTOs\PostData (title required
 * ≤255, content required, excerpt ≤500, meta fields ≤255/500); the backend
 * stays authoritative. `scheduled_at` is cross-checked against `status` here
 * too (`required_if:status,scheduled` + `after:now` on the server) so the user
 * gets instant feedback before the round-trip. The cover image upload is
 * validated separately by FileField/FileUpload, not part of this schema.
 */
export const postFormSchema = z
    .object({
        title: z.string().trim().min(1, 'Title is required.').max(255, 'Title must be 255 characters or fewer.'),
        content: z.string().trim().min(1, 'Content is required.'),
        excerpt: z.string().trim().max(500, 'Excerpt must be 500 characters or fewer.'),
        meta_title: z.string().trim().max(255, 'Meta title must be 255 characters or fewer.'),
        meta_description: z.string().trim().max(500, 'Meta description must be 500 characters or fewer.'),
        meta_keywords: z.string().trim().max(255, 'Meta keywords must be 255 characters or fewer.'),
        status: z.enum(['draft', 'published', 'scheduled']),
        scheduled_at: z.string().nullable(),
    })
    .refine((data) => data.status !== 'scheduled' || !!data.scheduled_at, {
        message: 'Pick a date and time to schedule this post.',
        path: ['scheduled_at'],
    });

export type PostFormValues = z.infer<typeof postFormSchema>;
