import type { ImagePassThroughOptions } from 'primevue/image';

/**
 * Shared pass-through theme for every `<Image preview>` instance (portfolio
 * cover thumbnails, gallery grid, table thumbnail). PrimeVue is registered
 * globally as `unstyled: true` (FRONTEND/SKILL.md §5), so a raw `Image` —
 * imported directly from `primevue/image` rather than through a Volt wrapper,
 * since Volt's curated ~50-component registry does not ship an `Image`
 * primitive (`npx volt-vue add Image` → "Component not found"; the same
 * direct-import pattern is already used for `Column` in `*Table.vue`) —
 * renders with zero default styling unless given a `pt` config.
 *
 * Uses the theme-INDEPENDENT `--photo-scrim` / `--on-photo` tokens
 * (globals.css) rather than the light/dark app tokens: the preview mask and
 * hover overlay always sit on top of arbitrary uploaded photos and must stay
 * legible in both themes, exactly like the Volt Dialog's own `mask` pt.
 */
export const imagePreviewPt: ImagePassThroughOptions = {
    previewMask: {
        class: 'flex items-center justify-center rounded-[inherit] cursor-pointer opacity-0 transition-opacity duration-200 hover:opacity-100 bg-[var(--photo-scrim)]',
    },
    previewIcon: {
        class: 'text-lg text-[var(--on-photo)]',
    },
    mask: {
        class: 'fixed inset-0 flex items-center justify-center bg-[var(--photo-scrim-strong)]',
    },
    toolbar: {
        class: 'absolute inset-x-0 top-0 flex items-center justify-end gap-1 p-3 bg-[var(--photo-scrim)]',
    },
    closeButton: {
        class: 'inline-flex h-8 w-8 items-center justify-center rounded-md text-[var(--on-photo)] transition-colors hover:bg-[var(--on-photo-hover-bg)]',
    },
    zoomInButton: {
        class: 'inline-flex h-8 w-8 items-center justify-center rounded-md text-[var(--on-photo)] transition-colors hover:bg-[var(--on-photo-hover-bg)]',
    },
    zoomOutButton: {
        class: 'inline-flex h-8 w-8 items-center justify-center rounded-md text-[var(--on-photo)] transition-colors hover:bg-[var(--on-photo-hover-bg)]',
    },
    rotateLeftButton: {
        class: 'inline-flex h-8 w-8 items-center justify-center rounded-md text-[var(--on-photo)] transition-colors hover:bg-[var(--on-photo-hover-bg)]',
    },
    rotateRightButton: {
        class: 'inline-flex h-8 w-8 items-center justify-center rounded-md text-[var(--on-photo)] transition-colors hover:bg-[var(--on-photo-hover-bg)]',
    },
    original: {
        class: 'max-h-[90vh] max-w-[90vw] object-contain',
    },
};
