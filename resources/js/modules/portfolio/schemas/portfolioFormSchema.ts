import { z } from 'zod';

/**
 * Client-side UX validation for the portfolio create/edit form (Zod v4).
 * Mirrors the backend rules in Modules\Portfolio\Application\DTOs\PortfolioData
 * (title/client_name required ≤255; project_type required ≤50; tech_stack
 * array ≤20 entries each ≤50 chars; live_url nullable URL ≤500; published_at
 * nullable date; description nullable ≤5000; sort_order nullable non-negative
 * integer) — the backend stays authoritative.
 * `live_url` and `published_at` are kept as empty-string-able strings: the form
 * omits them from the submit payload when blank so the backend clears the
 * column to `null` rather than failing its `url`/`date` rule against `''`.
 * `sort_order` is kept as a string since it drives a text input.
 * `tech_stack` is validated as a string array; the dialog stores a comma-
 * separated `tech_stack_text` and parses it before Zod / submit.
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
    tech_stack: z
        .array(z.string().trim().min(1).max(50, 'Each tech must be 50 characters or fewer.'))
        .max(20, 'Up to 20 technologies.'),
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

/** Split comma-separated tech labels (spaces inside a label are preserved). */
export function parseTechStack(text: string): string[] {
    return text
        .split(',')
        .map((item) => item.trim())
        .filter(Boolean);
}
