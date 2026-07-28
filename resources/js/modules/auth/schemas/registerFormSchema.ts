import { z } from 'zod';
import { passwordPolicySchema } from '@/modules/auth/schemas/resetPasswordFormSchema';

/**
 * UX validation for guest registration. Mirrors Fortify Password::default()
 * (12+ chars, mixed case, number, symbol) + confirmed + terms.
 */
export const registerFormSchema = z
    .object({
        first_name: z.string().trim().min(1, 'First name is required'),
        last_name: z.string().trim().optional(),
        email: z.string().trim().min(1, 'Email is required').email('Please enter a valid email address'),
        password: passwordPolicySchema,
        password_confirmation: z.string().min(1, 'Please confirm your password'),
        terms_and_conditions: z.boolean(),
    })
    .refine((data) => data.terms_and_conditions === true, {
        message: 'You must accept the terms and conditions',
        path: ['terms_and_conditions'],
    })
    .refine((data) => data.password === data.password_confirmation, {
        message: 'Passwords do not match',
        path: ['password_confirmation'],
    });

export type RegisterFormSchema = z.infer<typeof registerFormSchema>;
