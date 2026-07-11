/**
 * Derives a slug candidate from a service name, matching the backend's
 * `alpha_dash` rule (letters, numbers, dashes, underscores only). Used to
 * auto-fill the slug field on create until the user edits it directly — the
 * server stays authoritative on uniqueness.
 */
export function slugify(value: string): string {
    return value
        .trim()
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '')
        .slice(0, 100);
}
