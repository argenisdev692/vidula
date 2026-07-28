import { z } from 'zod';

/**
 * Client-side UX validation for product create/edit (Zod v4).
 * Mirrors Modules\Products\Application\DTOs\ProductData; backend stays authoritative.
 */
const optionalUrl = z
    .string()
    .trim()
    .max(2048, 'Must be 2048 characters or fewer.')
    .refine((value) => value === '' || z.string().url().safeParse(value).success, 'Enter a valid URL.');

const optionalUuid = z
    .string()
    .trim()
    .refine((value) => value === '' || z.string().uuid().safeParse(value).success, 'Select a valid client.');

export const productFormSchema = z
    .object({
        type: z.enum(['classroom', 'video_tutorial', 'video_pill']),
        title: z.string().trim().min(1, 'Title is required.').max(255, 'Title must be 255 characters or fewer.'),
        description: z.string().trim().max(20000, 'Description is too long.'),
        price: z.coerce.number().min(0, 'Price cannot be negative.').max(99_999_999.99),
        currency: z.string().trim().length(3, 'Use a 3-letter currency code.').regex(/^[A-Za-z]+$/, 'Currency must be letters.'),
        status: z.enum(['draft', 'published', 'archived']),
        level: z.string().trim().min(1, 'Level is required.').max(50),
        language: z.string().trim().min(1, 'Language is required.').max(10),
        client_uuid: optionalUuid,
        modality: z.enum(['online', 'presential', 'hybrid', '']),
        total_hours: z.union([z.literal(''), z.coerce.number().min(0).max(999_999.99)]),
        total_sessions: z.union([z.literal(''), z.coerce.number().int().min(0).max(1000)]),
        notes: z.string().trim().max(20000, 'Notes are too long.'),
        classroom_max_students: z.union([z.literal(''), z.coerce.number().int().min(1).max(10000)]),
        classroom_meet_url: optionalUrl,
        classroom_objectives: z.string().trim().max(20000),
        classroom_requirements: z.string().trim().max(20000),
        video_platform: z.enum(['youtube', 'vimeo', 'local', 'other', '']),
        video_playlist_url: optionalUrl,
        video_total_videos: z.union([z.literal(''), z.coerce.number().int().min(0).max(10000)]),
        video_total_duration_minutes: z.union([z.literal(''), z.coerce.number().int().min(0).max(1_000_000)]),
        video_target_audience: z.string().trim().max(20000),
    })
    .superRefine((data, ctx) => {
        if (data.type === 'classroom' && data.classroom_meet_url !== '') {
            const ok = z.string().url().safeParse(data.classroom_meet_url).success;
            if (!ok) {
                ctx.addIssue({ code: 'custom', path: ['classroom_meet_url'], message: 'Enter a valid meeting URL.' });
            }
        }
    });

export type ProductFormValues = z.infer<typeof productFormSchema>;
