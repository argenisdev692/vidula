import { z } from 'zod';

/**
 * Shared Fortify Password::default() UX rules (12+, mixed case, number, symbol).
 * Used by register + reset-password guest forms.
 */
export const passwordPolicySchema = z
    .string()
    .min(12, 'Password must be at least 12 characters')
    .regex(/[a-z]/, 'Must include a lowercase letter')
    .regex(/[A-Z]/, 'Must include an uppercase letter')
    .regex(/\d/, 'Must include a number')
    .regex(/[^a-zA-Z0-9]/, 'Must include a symbol');

export const resetPasswordFormSchema = z
    .object({
        email: z.string().trim().min(1, 'Email is required').email('Please enter a valid email address'),
        code: z.string().trim().min(4, 'Enter the code from your email'),
        password: passwordPolicySchema,
        password_confirmation: z.string().min(1, 'Please confirm your password'),
    })
    .refine((data) => data.password === data.password_confirmation, {
        message: 'Passwords do not match',
        path: ['password_confirmation'],
    });

export type ResetPasswordFormSchema = z.infer<typeof resetPasswordFormSchema>;
