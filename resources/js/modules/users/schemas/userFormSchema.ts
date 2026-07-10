import { z } from 'zod';

/**
 * Client-side UX validation for the invite (create) / edit user form (Zod v4).
 * Mirrors the backend rules in InviteUserData / UpdateUserData. The form is flat
 * (every text field is a string, '' when empty) — optional strings are normalised
 * '' → null before submit (see UserForm) so uniqueness checks and storage stay
 * clean. Coordinates are numeric and filled silently by the address autocomplete.
 *
 *   · first_name / last_name / email required; first_name & last_name are
 *     letters-only (no spaces, digits or symbols) — the UserForm sanitises input
 *     to a single Capitalized word before it ever reaches this schema
 *   · phone canonical E.164 string ≤50 (PhoneField emits it)
 *   · gender is a plain string bound to a fixed Select; the backend allowlist
 *     (Rule::in) stays authoritative
 *   · roles is invite-only (single role, submitted as a 0/1-length array);
 *     force_password_change is edit-only
 *
 * The backend stays authoritative — email/username uniqueness is enforced there.
 */

/** Unicode letters only — no whitespace, digits or punctuation. */
const NAME_PATTERN = /^\p{L}+$/u;

export const userFormSchema = z.object({
    first_name: z
        .string()
        .trim()
        .min(1, 'First name is required.')
        .max(255, 'First name must be 255 characters or fewer.')
        .regex(NAME_PATTERN, 'First name must contain letters only — no spaces.'),
    last_name: z
        .string()
        .trim()
        .min(1, 'Last name is required.')
        .max(255, 'Last name must be 255 characters or fewer.')
        .regex(NAME_PATTERN, 'Last name must contain letters only — no spaces.'),
    username: z.string().trim().max(255, 'Username must be 255 characters or fewer.'),
    email: z
        .string()
        .trim()
        .min(1, 'Email is required.')
        .email('Enter a valid email address.')
        .max(255, 'Email must be 255 characters or fewer.'),
    phone: z.string().trim().max(50, 'Phone must be 50 characters or fewer.'),
    date_of_birth: z.string().trim(),
    gender: z.string().trim(),
    address: z.string().trim().max(255, 'Address must be 255 characters or fewer.'),
    address_2: z.string().trim().max(255, 'Address line 2 must be 255 characters or fewer.'),
    zip_code: z.string().trim().max(20, 'ZIP / postal code must be 20 characters or fewer.'),
    city: z.string().trim().max(120, 'City must be 120 characters or fewer.'),
    state: z.string().trim().max(120, 'State must be 120 characters or fewer.'),
    country: z.string().trim().max(120, 'Country must be 120 characters or fewer.'),
    // ISO-3166 alpha-2 (uppercased by the form); blank allowed (mapped to null).
    country_code: z
        .string()
        .trim()
        .refine((value) => value === '' || /^[A-Z]{2}$/.test(value), 'Enter a 2-letter country code'),
    latitude: z.number().nullable(),
    longitude: z.number().nullable(),
    roles: z.array(z.string()),
    force_password_change: z.boolean(),
});

export type UserFormValues = z.infer<typeof userFormSchema>;
