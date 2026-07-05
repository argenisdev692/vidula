import { z } from 'zod';

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
    username: z.string().trim().max(255),
    email: z.string().trim().min(1, 'Email is required').email('Enter a valid email address').max(255),
    phone: z.string().trim().max(50),
    date_of_birth: z.string().trim(),
    gender: z.string().trim(),
    address: z.string().trim().max(255),
    address_2: z.string().trim().max(255),
    city: z.string().trim().max(120),
    state: z.string().trim().max(120),
    zip_code: z.string().trim().max(20),
    country: z.string().trim().max(120),
});

export type ProfileFormSchema = z.infer<typeof profileFormSchema>;
