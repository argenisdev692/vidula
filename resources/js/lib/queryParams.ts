/**
 * Query-string helpers shared by every server-side DataTable module.
 *
 * `buildExportUrl` is the single, domain-agnostic export-URL builder that every
 * `build{Entity}ExportUrl` helper delegates to, so the "drop pagination + append
 * format" logic can never drift between modules. Each module still owns its own
 * `build{Entity}QueryParams` (the set of filter keys genuinely varies), then
 * passes the resulting params here.
 */

/** Export formats accepted by every `/{resource}/export` endpoint. */
export type ExportFormat = 'csv' | 'xlsx' | 'pdf';

/**
 * Builds a `/{resource}/export?…` download URL from already-built query params.
 * Pagination is irrelevant to a full export, so `page` / `per_page` are dropped
 * and the requested `format` is appended.
 */
export function buildExportUrl(
    baseUrl: string,
    params: Record<string, string | number>,
    format: ExportFormat,
): string {
    const search = new URLSearchParams(
        Object.fromEntries(Object.entries(params).map(([key, value]) => [key, String(value)])),
    );
    search.delete('page');
    search.delete('per_page');
    search.set('format', format);

    return `${baseUrl}?${search.toString()}`;
}
