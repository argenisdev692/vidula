import { isValidPhoneNumber } from 'libphonenumber-js';
import { z } from 'zod';

/**
 * Client-side UX validation for the CRM client create/edit form (Zod v4).
 * Mirrors Modules\Clients\Application\DTOs\ClientData; backend stays authoritative.
 * Phone is E.164 from PhoneField. Empty optional strings are allowed and mapped
 * to null on submit.
 */
const optionalUrl = z
    .string()
    .trim()
    .max(255, 'Must be 255 characters or fewer.')
    .refine((value) => value === '' || z.string().url().safeParse(value).success, 'Enter a valid URL.');

export const clientFormSchema = z.object({
    client_name: z
        .string()
        .trim()
        .min(1, 'Name is required.')
        .max(255, 'Name must be 255 characters or fewer.'),
    email: z
        .string()
        .trim()
        .max(255, 'Email must be 255 characters or fewer.')
        .refine((value) => value === '' || z.string().email().safeParse(value).success, 'Enter a valid email address.'),
    status: z.enum(['DRAFT', 'ACTIVE', 'ARCHIVED']),
    phone: z
        .string()
        .trim()
        .min(1, 'Phone is required.')
        .max(20, 'Phone must be 20 characters or fewer.')
        .refine((value) => isValidPhoneNumber(value), 'Enter a valid phone number.'),
    address: z.string().trim().max(255, 'Address must be 255 characters or fewer.'),
    tax_id: z.string().trim().max(255, 'Tax ID must be 255 characters or fewer.'),
    nif: z.string().trim().max(255, 'NIF must be 255 characters or fewer.'),
    website: optionalUrl,
    facebook_link: optionalUrl,
    instagram_link: optionalUrl,
    linkedin_link: optionalUrl,
    twitter_link: optionalUrl,
    notes: z.string().trim().max(5000, 'Notes must be 5000 characters or fewer.'),
});

export type ClientFormValues = z.infer<typeof clientFormSchema>;
