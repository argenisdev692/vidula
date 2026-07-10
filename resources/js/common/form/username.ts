/**
 * Single source of truth for the username rules, shared by the users (invite /
 * edit) and profile (self-service) forms so the client mirrors the backend
 * exactly. A username is optional, but when present it must be:
 *
 *   · letters and digits only — no spaces, underscores, hyphens or symbols
 *   · between USERNAME_MIN and USERNAME_MAX characters long
 *
 * `sanitizeUsername` is applied on every keystroke (v-model setter) so the field
 * can never hold an out-of-shape value: invalid characters are dropped and the
 * result is hard-capped at USERNAME_MAX. Length (min) and emptiness are surfaced
 * as validation messages instead — never silently blocked while typing.
 */
export const USERNAME_MIN = 8;
export const USERNAME_MAX = 15;

/** Letters and digits only (ASCII) — no whitespace or punctuation. */
export const USERNAME_PATTERN = /^[a-zA-Z0-9]+$/;

/** Strip everything but letters/digits and hard-cap the length at USERNAME_MAX. */
export function sanitizeUsername(value: string): string {
    return value.replace(/[^a-zA-Z0-9]/g, '').slice(0, USERNAME_MAX);
}

/**
 * Validity check for a *non-empty* username (empty is handled as optional by the
 * caller). Used both as the availability composable's `validate` gate and by the
 * Zod schemas.
 */
export function isValidUsername(value: string): boolean {
    return USERNAME_PATTERN.test(value) && value.length >= USERNAME_MIN && value.length <= USERNAME_MAX;
}

/** User-facing message describing the shape rule. */
export const USERNAME_RULE_MESSAGE = `Username must be ${USERNAME_MIN}–${USERNAME_MAX} letters or numbers — no spaces or symbols.`;
