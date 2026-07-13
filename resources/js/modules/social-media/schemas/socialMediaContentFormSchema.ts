import { z } from 'zod';

/**
 * Client-side UX validation for the social media review/edit form (Zod v4).
 * Mirrors the backend rules in Modules\SocialMedia\Application\DTOs\UpdateSocialMediaContentData
 * (headline required ≤255, body required, call_to_action required ≤500,
 * hashtags array ≤20 entries each ≤50 chars, status enum, scheduled_at
 * required_if:status,scheduled + after:now); the backend stays authoritative.
 * There is no manual "create" form — content is always AI-born — so this
 * schema only ever backs the Edit page.
 */
export const socialMediaContentFormSchema = z
    .object({
        headline: z.string().trim().min(1, 'Headline is required.').max(255, 'Headline must be 255 characters or fewer.'),
        body: z.string().trim().min(1, 'Body is required.'),
        call_to_action: z.string().trim().min(1, 'Call to action is required.').max(500, 'Call to action must be 500 characters or fewer.'),
        hashtags: z.array(z.string().trim().max(50, 'Each hashtag must be 50 characters or fewer.')).max(20, 'Up to 20 hashtags.'),
        status: z.enum(['draft', 'ready', 'needs_review', 'published', 'scheduled']),
        scheduled_at: z.string().nullable(),
    })
    .refine((data) => data.status !== 'scheduled' || !!data.scheduled_at, {
        message: 'Pick a date and time to schedule this content.',
        path: ['scheduled_at'],
    });

export type SocialMediaContentFormValues = z.infer<typeof socialMediaContentFormSchema>;
