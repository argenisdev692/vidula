/**
 * Defensive gate for server-generated SVG (2FA QR) before `v-html`.
 * Rejects anything that is not a single SVG document or that embeds scripts.
 */
export function sanitizeTrustedSvg(svg: string): string {
    const trimmed = svg.trim();

    if (!trimmed.startsWith('<svg') || !trimmed.includes('</svg>')) {
        return '';
    }

    if (/<script[\s>]/i.test(trimmed) || /\bon\w+\s*=/i.test(trimmed)) {
        return '';
    }

    return trimmed;
}
