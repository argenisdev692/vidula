import { z } from 'zod';

/**
 * Client-side UX validation for the contact-support create/edit form (Zod v4).
 * Mirrors the backend rules in Modules\ContactSupport\Application\DTOs\ContactSupportData
 * (all required; phone canonical E.164 ≤20 — PhoneField emits it); the backend
 * stays authoritative. The `readed` flag is intentionally absent — it is toggled
 * only through the dedicated mark-as-read action, never from this form.
 */
export const contactSupportFormSchema = z.object({
    first_name: z
        .string()
        .trim()
        .min(1, 'First name is required.')
        .max(255, 'First name must be 255 characters or fewer.'),
    last_name: z
        .string()
        .trim()
        .min(1, 'Last name is required.')
        .max(255, 'Last name must be 255 characters or fewer.'),
    email: z
        .string()
        .trim()
        .min(1, 'Email is required.')
        .email('Enter a valid email address.')
        .max(255, 'Email must be 255 characters or fewer.'),
    phone: z
        .string()
        .trim()
        .min(1, 'Phone is required.')
        .max(20, 'Phone must be 20 characters or fewer.'),
    subject: z
        .string()
        .trim()
        .min(1, 'Subject is required.')
        .max(150, 'Subject must be 150 characters or fewer.'),
    message: z
        .string()
        .trim()
        .min(1, 'Message is required.')
        .max(5000, 'Message must be 5000 characters or fewer.'),
    sms_consent: z.boolean(),
});

export type ContactSupportFormValues = z.infer<typeof contactSupportFormSchema>;
