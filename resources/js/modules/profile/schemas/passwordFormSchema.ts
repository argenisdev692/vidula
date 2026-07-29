import { z } from 'zod';

/**
 * Client-side UX validation for the change-password form. Mirrors Fortify's
 * Password::default() policy (8+ chars, mixed case, number, symbol, confirmed).
 */
export const passwordFormSchema = z
    .object({
        current_password: z.string().min(1, 'Current password is required'),
        password: z
            .string()
            .min(8, 'Password must be at least 8 characters')
            .regex(/[a-z]/, 'Must include a lowercase letter')
            .regex(/[A-Z]/, 'Must include an uppercase letter')
            .regex(/\d/, 'Must include a number')
            .regex(/[^a-zA-Z0-9]/, 'Must include a symbol'),
        password_confirmation: z.string().min(1, 'Please confirm your new password'),
    })
    .refine((data) => data.password === data.password_confirmation, {
        message: 'Passwords do not match',
        path: ['password_confirmation'],
    });

export type PasswordFormSchema = z.infer<typeof passwordFormSchema>;
