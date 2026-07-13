import { z } from 'zod';

/**
 * Client-side UX validation for the campaign review/edit form (Zod v4).
 * Mirrors the backend rules in Modules\Campaigns\Application\DTOs\UpdateCampaignData
 * (headline required ≤255, primary_text required, description optional ≤500,
 * call_to_action required ≤100, hashtags array ≤20 entries each ≤50 chars,
 * lead_form_questions array ≤10 entries each ≤255 chars, status enum,
 * scheduled_at required_if:status,scheduled + after:now); the backend stays
 * authoritative. There is no manual "create" form — a campaign is always
 * AI-born — so this schema only ever backs the Edit page.
 */
export const campaignFormSchema = z
    .object({
        headline: z.string().trim().min(1, 'Headline is required.').max(255, 'Headline must be 255 characters or fewer.'),
        primary_text: z.string().trim().min(1, 'Primary text is required.'),
        description: z.string().trim().max(500, 'Description must be 500 characters or fewer.').nullable(),
        call_to_action: z.string().trim().min(1, 'Call to action is required.').max(100, 'Call to action must be 100 characters or fewer.'),
        hashtags: z.array(z.string().trim().max(50, 'Each hashtag must be 50 characters or fewer.')).max(20, 'Up to 20 hashtags.'),
        lead_form_questions: z.array(z.string().trim().max(255, 'Each question must be 255 characters or fewer.')).max(10, 'Up to 10 questions.'),
        status: z.enum(['draft', 'ready', 'needs_review', 'published', 'scheduled']),
        scheduled_at: z.string().nullable(),
    })
    .refine((data) => data.status !== 'scheduled' || !!data.scheduled_at, {
        message: 'Pick a date and time to schedule this campaign.',
        path: ['scheduled_at'],
    });

export type CampaignFormValues = z.infer<typeof campaignFormSchema>;
