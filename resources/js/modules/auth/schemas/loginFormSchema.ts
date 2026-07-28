import { z } from 'zod';

/**
 * UX validation for the password login tab. Length floor matches Fortify
 * Password::default() (12+); mixed-case / symbol rules are enforced server-side
 * on register/reset — login only needs a non-empty credential check.
 */
export const loginFormSchema = z.object({
    email: z.string().trim().min(1, 'Email is required').email('Please enter a valid email address'),
    password: z.string().min(12, 'Password must be at least 12 characters'),
    remember: z.boolean().optional(),
});

export type LoginFormSchema = z.infer<typeof loginFormSchema>;

/** Lightweight email check shared by OTP / forgot-password tabs. */
export const emailOnlySchema = z.object({
    email: z.string().trim().min(1, 'Email is required').email('Please enter a valid email address'),
});
