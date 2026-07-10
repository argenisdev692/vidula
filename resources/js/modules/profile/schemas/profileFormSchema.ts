import { isValidPhoneNumber } from 'libphonenumber-js';
import { z } from 'zod';
import { isValidUsername, USERNAME_RULE_MESSAGE } from '@/common/form/username';

/**
 * Client-side UX validation for the profile form (Zod v4). Mirrors the shape of
 * the backend rules in App\Actions\Fortify\UpdateUserProfileInformation, but the
 * backend stays authoritative — uniqueness of email/username can only be
 * verified server-side and comes back through Inertia's `updateProfileInformation`
 * error bag. Empty strings are allowed for optional fields; the page transforms
 * them to `null` before submitting.
 */
export const profileFormSchema = z.object({
    first_name: z.string().trim().min(1, 'First name is required').max(255),
    last_name: z.string().trim().max(255),
    // Optional — but when provided must be 8–15 letters/digits (mirrors backend).
    username: z
        .string()
        .trim()
        .refine((value) => value === '' || isValidUsername(value), USERNAME_RULE_MESSAGE),
    email: z.string().trim().min(1, 'Email is required').email('Enter a valid email address').max(255),
    // Stored as E.164 by PhoneField; validate the value is a real phone number
    // (empty is allowed — the page maps blank to null before submitting).
    phone: z
        .string()
        .trim()
        .refine((value) => value === '' || isValidPhoneNumber(value), 'Enter a valid phone number'),
    date_of_birth: z.string().trim(),
    gender: z.string().trim(),
    address: z.string().trim().max(255),
    address_2: z.string().trim().max(255),
    city: z.string().trim().max(120),
    state: z.string().trim().max(120),
    zip_code: z.string().trim().max(20),
    country: z.string().trim().max(120),
    // ISO-3166 alpha-2 (uppercased by the page); blank allowed (mapped to null).
    country_code: z
        .string()
        .trim()
        .refine((value) => value === '' || /^[A-Z]{2}$/.test(value), 'Enter a 2-letter country code'),
    latitude: z.number().nullable(),
    longitude: z.number().nullable(),
});

export type ProfileFormSchema = z.infer<typeof profileFormSchema>;
