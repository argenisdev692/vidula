import { z } from 'zod';

/**
 * Client-side UX validation for the permission create/edit form (Zod v4).
 * Mirrors the backend rules in
 * Modules\Authorization\Application\DTOs\PermissionData: the name follows the
 * project convention `{ACTION}_{MODULE}` (upper snake case) enforced by the
 * regex. Uniqueness stays authoritative on the server.
 */
export const permissionFormSchema = z.object({
    name: z
        .string()
        .trim()
        .min(1, 'Name is required.')
        .max(125, 'Name must be 125 characters or fewer.')
        .regex(/^[A-Z][A-Z0-9_]*$/, 'Use UPPER_SNAKE_CASE, e.g. VIEW_ANY_USERS.'),
});

export type PermissionFormValues = z.infer<typeof permissionFormSchema>;
