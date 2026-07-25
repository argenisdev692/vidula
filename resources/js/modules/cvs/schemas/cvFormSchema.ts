import { z } from 'zod';

/**
 * Client-side UX validation for CV create/edit (Zod v4).
 * Mirrors Modules\Cvs\Application\DTOs\CvData; backend stays authoritative.
 * File is required on create; optional on edit (replacement).
 */
export const cvFormSchema = z.object({
    title: z
        .string()
        .trim()
        .min(1, 'Title is required.')
        .max(255, 'Title must be 255 characters or fewer.'),
    niche: z.enum(['fullstack', 'other']),
    is_primary: z.boolean(),
    file: z.instanceof(File).nullable(),
});

export type CvFormValues = z.infer<typeof cvFormSchema>;

export function cvFormSchemaForMode(mode: 'create' | 'edit') {
    return cvFormSchema.superRefine((data, ctx) => {
        if (mode === 'create' && data.file === null) {
            ctx.addIssue({
                code: 'custom',
                path: ['file'],
                message: 'A PDF or Markdown file is required.',
            });
        }

        if (data.file !== null) {
            const name = data.file.name.toLowerCase();
            const ok = name.endsWith('.pdf') || name.endsWith('.md') || name.endsWith('.markdown');
            if (!ok) {
                ctx.addIssue({
                    code: 'custom',
                    path: ['file'],
                    message: 'Only PDF or Markdown files are allowed.',
                });
            }
            if (data.file.size > 5 * 1024 * 1024) {
                ctx.addIssue({
                    code: 'custom',
                    path: ['file'],
                    message: 'File must be 5 MB or smaller.',
                });
            }
        }
    });
}
