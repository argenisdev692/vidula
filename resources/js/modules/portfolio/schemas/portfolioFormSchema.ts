import { z } from 'zod';

/**
 * Client-side UX validation for the portfolio create/edit form (Zod v4).
 * Mirrors the backend rules in Modules\Portfolio\Application\DTOs\PortfolioData
 * (title/client_name required ≤255; project_type required ≤50; live_url
 * nullable URL ≤500; published_at nullable date; description nullable ≤5000;
 * sort_order nullable non-negative integer) — the backend stays authoritative.
 * `live_url` and `published_at` are kept as empty-string-able strings: the form
 * omits them from the submit payload when blank so the backend clears the
 * column to `null` rather than failing its `url`/`date` rule against `''`.
 * `sort_order` is kept as a string since it drives a text input.
 */
export const portfolioFormSchema = z.object({
    title: z.string().trim().min(1, 'Title is required.').max(255, 'Title must be 255 characters or fewer.'),
    client_name: z
        .string()
        .trim()
        .min(1, 'Client name is required.')
        .max(255, 'Client name must be 255 characters or fewer.'),
    project_type: z
        .string()
        .trim()
        .min(1, 'Project type is required.')
        .max(50, 'Project type must be 50 characters or fewer.'),
    live_url: z
        .string()
        .trim()
        .max(500, 'Live URL must be 500 characters or fewer.')
        .refine((value) => value === '' || z.url().safeParse(value).success, 'Enter a valid URL.'),
    published_at: z.string().trim(),
    is_public: z.boolean(),
    description: z.string().trim().max(5000, 'Description must be 5000 characters or fewer.'),
    sort_order: z.string().trim().regex(/^\d*$/, 'Sort order must be a whole number.'),
});

export type PortfolioFormValues = z.infer<typeof portfolioFormSchema>;
