import { z } from 'zod';
import { AI_PROVIDER_VALUES, LOCATION_SCOPE_VALUES } from '../helpers/options';

/**
 * Client-side UX validation for POST /resume-studio/runs (Zod v4).
 * Backend StartStudioRunData remains authoritative.
 */
export const startStudioRunSchema = z
    .object({
        cv_uuid: z.string().uuid('Select a CV.'),
        mode: z.enum(['career', 'other']),
        provider: z.enum(AI_PROVIDER_VALUES),
        keywords: z.string().max(500).optional().nullable(),
        targeting_prompt: z.string().max(5000).optional().nullable(),
        github_username: z.string().max(255).optional().nullable(),
        github_selected_repos: z.array(z.string().max(255)).max(20).optional().nullable(),
        github_extra_prompt: z.string().max(5000).optional().nullable(),
        deep_extract: z.boolean(),
        target_job_title: z.string().max(255).optional().nullable(),
        job_description: z.string().max(20000).optional().nullable(),
        location_scope: z.enum(LOCATION_SCOPE_VALUES).optional().nullable(),
        search_language: z.enum(['es', 'en', 'both']),
        resume_language: z.enum(['en', 'es', 'pt-PT']),
    })
    .superRefine((data, ctx) => {
        if (data.mode === 'other' && !data.targeting_prompt?.trim()) {
            ctx.addIssue({
                code: 'custom',
                path: ['targeting_prompt'],
                message: 'Targeting prompt is required for Other niche mode.',
            });
        }
    });

export type StartStudioRunValues = z.infer<typeof startStudioRunSchema>;
