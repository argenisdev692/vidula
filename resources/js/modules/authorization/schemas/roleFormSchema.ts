import { z } from 'zod';

/**
 * Client-side UX validation for the role create/edit form (Zod v4). Mirrors the
 * backend rules in Modules\Authorization\Application\DTOs\RoleData (name required
 * ≤125, `[A-Za-z0-9 _-]`; permissions is the full set of names to sync, so an
 * empty array revokes every grant). The backend stays authoritative — uniqueness
 * and the `permissions.* exists` check are enforced server-side.
 */
export const roleFormSchema = z.object({
    name: z
        .string()
        .trim()
        .min(1, 'Name is required.')
        .max(125, 'Name must be 125 characters or fewer.')
        .regex(/^[A-Za-z0-9 _-]+$/, 'Use only letters, numbers, spaces, hyphens or underscores.'),
    permissions: z.array(z.string()),
});

export type RoleFormValues = z.infer<typeof roleFormSchema>;
