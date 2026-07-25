import { isValidPhoneNumber } from 'libphonenumber-js';
import { z } from 'zod';

/**
 * Client-side UX validation for the company settings form (Zod v4). Mirrors the
 * backend rules in Modules\Company\Application\DTOs\UpdateCompanyData, but the
 * backend stays authoritative. Optional fields allow empty strings; the page
 * transforms blanks to `null` before submitting. Brand assets (logos, signature)
 * are uploaded through their own endpoints and are NOT part of this schema.
 */

/** Optional http(s) URL — allows blank (page maps blank → null before submit). */
function isHttpUrl(value: string): boolean {
    try {
        const url = new URL(value);
        return url.protocol === 'http:' || url.protocol === 'https:';
    } catch {
        return false;
    }
}

const optionalUrl = (max = 255) =>
    z
        .string()
        .trim()
        .max(max)
        .refine((value) => value === '' || isHttpUrl(value), 'Enter a valid URL');

export const companyFormSchema = z.object({
    company_name: z.string().trim().min(1, 'Company name is required').max(255),
    name: z.string().trim().max(255),
    description: z.string().trim().max(2000),
    email: z
        .string()
        .trim()
        .max(255)
        .refine((value) => value === '' || z.string().email().safeParse(value).success, 'Enter a valid email address'),
    // Stored as E.164 by PhoneField; empty is allowed (mapped to null before submit).
    phone: z
        .string()
        .trim()
        .refine((value) => value === '' || isValidPhoneNumber(value), 'Enter a valid phone number'),
    nif_nipc: z.string().trim().max(50),
    nie: z.string().trim().max(50),
    bank_beneficiary: z.string().trim().max(255),
    bank_iban: z.string().trim().max(64),
    bank_bic: z.string().trim().max(32),
    bank_name: z.string().trim().max(255),
    invoice_notes: z.string().trim().max(5000),
    address: z.string().trim().max(1000),
    address_2: z.string().trim().max(1000),
    zip_code: z.string().trim().max(20),
    city: z.string().trim().max(100),
    state: z.string().trim().max(100),
    country: z.string().trim().max(100),
    // ISO-3166 alpha-2 (uppercased by the form); blank is allowed (mapped to null).
    country_code: z
        .string()
        .trim()
        .refine((value) => value === '' || /^[A-Z]{2}$/.test(value), 'Enter a 2-letter country code'),
    website: optionalUrl(),
    facebook_link: optionalUrl(),
    instagram_link: optionalUrl(),
    linkedin_link: optionalUrl(),
    twitter_link: optionalUrl(),
    tiktok_link: optionalUrl(),
    latitude: z.number().nullable(),
    longitude: z.number().nullable(),
});

export type CompanyFormValues = z.infer<typeof companyFormSchema>;
