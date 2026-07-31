import { z } from 'zod';
import { CLIENT_TYPE_VALUES } from '../helpers/options';

/**
 * Client-side UX validation for the appointment create/edit form (Zod v4).
 * Mirrors the backend rules in `Modules\Appointment\Application\DTOs\AppointmentData`,
 * with one deliberately STRICTER rule the backend does not enforce: first/last
 * name are capped to 3–20 letters (no spaces, digits or punctuation) — the form
 * sanitises every keystroke to a single Capitalized word before it ever reaches
 * this schema, so the pattern only ever rejects an empty or too-short result.
 * The backend (`max:255`) stays authoritative on the outer bound.
 *
 * `scheduled_at` is CREATE-only (an optional initial meeting time submitted
 * alongside — never part of — the `AppointmentData` payload; see
 * `AppointmentController::store`); it is absent from the edit payload entirely,
 * so it is validated here as a plain optional string.
 *
 * `client_type` enum is derived from {@link ../helpers/options}.
 */

/** Unicode letters only — no whitespace, digits or punctuation. */
const NAME_PATTERN = /^\p{L}+$/u;
export const NAME_MIN = 3;
export const NAME_MAX = 20;

export const appointmentFormSchema = z
    .object({
        first_name: z
            .string()
            .trim()
            .min(NAME_MIN, `First name must be at least ${NAME_MIN} letters.`)
            .max(NAME_MAX, `First name must be ${NAME_MAX} letters or fewer.`)
            .regex(NAME_PATTERN, 'First name must contain letters only — no spaces.'),
        last_name: z
            .string()
            .trim()
            .min(NAME_MIN, `Last name must be at least ${NAME_MIN} letters.`)
            .max(NAME_MAX, `Last name must be ${NAME_MAX} letters or fewer.`)
            .regex(NAME_PATTERN, 'Last name must contain letters only — no spaces.'),
        client_type: z.enum(CLIENT_TYPE_VALUES),
        company_name: z.string().trim().max(255, 'Company name must be 255 characters or fewer.'),
        service_uuid: z.union([z.string().uuid(), z.literal('')]),
        email: z
            .string()
            .trim()
            .min(1, 'Email is required.')
            .email('Enter a valid email address.')
            .max(255, 'Email must be 255 characters or fewer.'),
        phone: z.string().trim().max(20, 'Phone must be 20 characters or fewer.'),
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
        sms_consent: z.boolean(),
        notes: z.string().trim().max(5000, 'Notes must be 5000 characters or fewer.'),
        owner: z.string().trim().max(255, 'Owner must be 255 characters or fewer.'),
        scheduled_at: z.string().trim(),
    })
    .refine((data) => data.client_type !== 'company' || data.company_name !== '', {
        message: 'Company name is required for a company lead.',
        path: ['company_name'],
    });

export type AppointmentFormValues = z.infer<typeof appointmentFormSchema>;
