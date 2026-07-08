import { z } from 'zod';

/**
 * Client-side UX validation for the invite (create) / edit user form (Zod v4).
 * Mirrors the backend rules in InviteUserData / UpdateUserData:
 *   · first_name required ≤255
 *   · last_name / username / address_2 optional ≤255 (empty ⇒ normalised to null
 *     before submit — see UserFormDialog)
 *   · email required, valid, ≤255
 *   · phone optional, canonical E.164 string ≤50 (PhoneField emits it)
 *   · force_password_change is EDIT-only (InviteUserData has no such field)
 *
 * The backend stays authoritative — email/username uniqueness is enforced there.
 */
export const userFormSchema = z.object({
    first_name: z
        .string()
        .trim()
        .min(1, 'First name is required.')
        .max(255, 'First name must be 255 characters or fewer.'),
    last_name: z.string().trim().max(255, 'Last name must be 255 characters or fewer.'),
    email: z
        .string()
        .trim()
        .min(1, 'Email is required.')
        .email('Enter a valid email address.')
        .max(255, 'Email must be 255 characters or fewer.'),
    username: z.string().trim().max(255, 'Username must be 255 characters or fewer.'),
    phone: z
        .string()
        .trim()
        .max(50, 'Phone must be 50 characters or fewer.')
        .nullable(),
    address_2: z.string().trim().max(255, 'Address line 2 must be 255 characters or fewer.'),
    force_password_change: z.boolean(),
});

export type UserFormValues = z.infer<typeof userFormSchema>;
