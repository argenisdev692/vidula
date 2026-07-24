import { isValidPhoneNumber } from 'libphonenumber-js';
import { z } from 'zod';

/**
 * Client-side UX validation for the LMS student create/edit form (Zod v4).
 * Mirrors Modules\Students\Application\DTOs\StudentData; backend stays authoritative.
 * Phone is optional E.164 from PhoneField. Empty optional strings map to null on submit.
 */
const optionalUrl = z
    .string()
    .trim()
    .max(2048, 'Must be 2048 characters or fewer.')
    .refine((value) => value === '' || z.string().url().safeParse(value).success, 'Enter a valid URL.');

export const studentFormSchema = z.object({
    name: z
        .string()
        .trim()
        .min(1, 'Name is required.')
        .max(255, 'Name must be 255 characters or fewer.'),
    email: z
        .string()
        .trim()
        .max(255, 'Email must be 255 characters or fewer.')
        .refine((value) => value === '' || z.string().email().safeParse(value).success, 'Enter a valid email address.'),
    phone: z
        .string()
        .trim()
        .max(20, 'Phone must be 20 characters or fewer.')
        .refine((value) => value === '' || isValidPhoneNumber(value), 'Enter a valid phone number.'),
    dni: z.string().trim().max(50, 'DNI must be 50 characters or fewer.'),
    address: z.string().trim().max(255, 'Address must be 255 characters or fewer.'),
    avatar: optionalUrl,
    notes: z.string().trim().max(5000, 'Notes must be 5000 characters or fewer.'),
    status: z.enum(['DRAFT', 'ACTIVE', 'ARCHIVED']),
    active: z.boolean(),
});

export type StudentFormValues = z.infer<typeof studentFormSchema>;
