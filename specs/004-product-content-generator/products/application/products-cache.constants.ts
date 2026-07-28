export const PRODUCTS_CACHE_PATTERN = 'http:*:/products*';

export const PRODUCT_NOT_FOUND = 'Product not found';
export const PRODUCT_SLUG_CONFLICT = 'A product with this slug already exists';
export const GENERATION_NOT_FOUND = 'Content generation not found';
export const GENERATION_IN_FLIGHT =
  'A content generation is already in progress for this product';
export const GENERATION_INVALID_MARKDOWN =
  'Markdown index is empty or could not be parsed into sessions/topics';
export const GENERATION_TYPE_UNSUPPORTED =
  'Content generation is not supported for this product type';

/** Max markdown seed size (bytes) — FR-NFR size cap. */
export const MAX_SOURCE_MARKDOWN_BYTES = 1_048_576;

/** Caps for seed outline (abuse protection). */
export const MAX_SESSIONS_PER_PRODUCT = 200;
export const MAX_TOPICS_PER_PRODUCT = 2000;

export function toProductSlug(input: string): string {
  return input
    .toLowerCase()
    .trim()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9\s-]/g, '')
    .replace(/[\s_]+/g, '-')
    .replace(/-+/g, '-')
    .replace(/^-+|-+$/g, '');
}
