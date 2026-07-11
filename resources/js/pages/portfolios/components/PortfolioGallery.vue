<script setup lang="ts">
/**
 * Gallery image manager for a single portfolio project, shown on the Show
 * page. Three concerns, each its own endpoint (there is no single "save
 * gallery" action — every change persists immediately):
 *
 *   · Add     → POST   /portfolios/{uuid}/gallery          (one file per request —
 *               the backend `AddPortfolioGalleryImageData` accepts exactly one
 *               `image`, so multi-file drops/selects are uploaded sequentially)
 *   · Reorder → POST   /portfolios/{uuid}/gallery/reorder   ({ uuids: [...] },
 *               array position becomes the new `sort_order`)
 *   · Remove  → DELETE /portfolios/{uuid}/gallery/{mediaUuid}
 *
 * All three return `back()` redirects, so every request re-renders the Show
 * page with a fresh `portfolio.gallery` — no local cache to invalidate beyond
 * the `localImages` mirror kept for optimistic drag feedback.
 *
 * Reordering uses `vuedraggable` (Vue 3 wrapper over SortableJS — approved as a
 * dependency specifically for touch support + smooth reorder animation, native
 * HTML5 DnD has neither) with a keyboard-accessible "move left / move right"
 * fallback on every thumbnail, since Sortable-driven dragging still has no
 * keyboard equivalent (WCAG 2.1.1).
 *
 * Each thumbnail is a raw `primevue/image` in `preview` mode (lightbox: zoom /
 * rotate / close) rather than a plain `<img>` — Volt's curated registry has no
 * `Image` primitive, so it's imported directly and themed via the shared
 * {@see imagePreviewPt}, same pattern already used for `Column` in `*Table.vue`.
 *
 * "Replace" has no dedicated backend endpoint — it is composed client-side as
 * upload-new → delete-old → reorder-back-into-place (see `replaceImage`),
 * since `AddPortfolioGalleryImageHandler` always appends at the end.
 *
 * Layer: Pages/portfolios/components — page-private, not reused elsewhere.
 */
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import Image from 'primevue/image';
import draggable from 'vuedraggable';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import ConfirmDialog from '@/common/data-table/ConfirmDialog.vue';
import { useConfirmAction } from '@/common/data-table/useConfirmAction';
import { imagePreviewPt } from '@/common/media/imagePreviewPt';
import type { PortfolioGalleryImage } from '@/modules/portfolio/types';

const props = defineProps<{
    portfolioUuid: string;
    images: PortfolioGalleryImage[];
}>();

const toast = useToast();

const ACCEPT = ['image/jpeg', 'image/png', 'image/webp'];
const MAX_SIZE_MB = 4;

/** Local mirror so a drag reorder feels instant; resynced whenever the server
 *  echoes a fresh `gallery` prop (after any add/remove/reorder round-trip). */
const localImages = ref<PortfolioGalleryImage[]>([...props.images]);
watch(
    () => props.images,
    (next) => {
        localImages.value = [...next];
    },
);

const uploading = ref<boolean>(false);
const reordering = ref<boolean>(false);
const dropActive = ref<boolean>(false);
const inputRef = ref<HTMLInputElement | null>(null);
const replaceInputRef = ref<HTMLInputElement | null>(null);
const replacingUuid = ref<string | null>(null);
const pendingReplace = ref<{ index: number; image: PortfolioGalleryImage } | null>(null);

function openPicker(): void {
    inputRef.value?.click();
}

function validate(file: File): string | null {
    if (!ACCEPT.includes(file.type)) {
        return 'Unsupported file type.';
    }
    if (file.size > MAX_SIZE_MB * 1024 * 1024) {
        return `File must be ${MAX_SIZE_MB} MB or smaller.`;
    }
    return null;
}

/** Uploads one file at a time — the backend accepts exactly one `image` per request. */
function uploadSequential(queue: File[]): void {
    const [file, ...rest] = queue;
    if (!file) {
        uploading.value = false;
        toast.add({ severity: 'success', summary: 'Gallery updated', life: 3000 });
        return;
    }
    uploading.value = true;
    router.post(
        `/portfolios/${props.portfolioUuid}/gallery`,
        { image: file },
        {
            forceFormData: true,
            preserveScroll: true,
            preserveState: true,
            onError: () => {
                toast.add({ severity: 'error', summary: 'Upload failed', detail: file.name, life: 5000 });
            },
            onFinish: () => uploadSequential(rest),
        },
    );
}

function enqueue(fileList: FileList | null): void {
    const files = Array.from(fileList ?? []);
    if (files.length === 0) {
        return;
    }
    const valid: File[] = [];
    for (const file of files) {
        const error = validate(file);
        if (error) {
            toast.add({ severity: 'error', summary: 'Skipped file', detail: `${file.name}: ${error}`, life: 5000 });
            continue;
        }
        valid.push(file);
    }
    if (valid.length > 0) {
        uploadSequential(valid);
    }
}

function onInputChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    enqueue(input.files);
    input.value = '';
}

function onDropzoneDrop(event: DragEvent): void {
    dropActive.value = false;
    enqueue(event.dataTransfer?.files ?? null);
}

/* ── Replace (upload-new → delete-old → reorder-back-into-place) ───────── */
function openReplacePicker(index: number, image: PortfolioGalleryImage): void {
    pendingReplace.value = { index, image };
    replaceInputRef.value?.click();
}

function onReplaceInputChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    input.value = '';

    const target = pendingReplace.value;
    pendingReplace.value = null;
    if (!file || !target) {
        return;
    }

    const error = validate(file);
    if (error) {
        toast.add({ severity: 'error', summary: 'Skipped file', detail: `${file.name}: ${error}`, life: 5000 });
        return;
    }

    replaceImage(target.index, target.image, file);
}

/**
 * There is no atomic "replace" endpoint — `AddPortfolioGalleryImageHandler`
 * always appends the new image at the end of the collection. So a replace is:
 * upload the new file, delete the old one, then reorder the new image back
 * into the slot the old one occupied.
 */
function replaceImage(index: number, oldImage: PortfolioGalleryImage, file: File): void {
    replacingUuid.value = oldImage.uuid;
    router.post(
        `/portfolios/${props.portfolioUuid}/gallery`,
        { image: file },
        {
            forceFormData: true,
            preserveScroll: true,
            preserveState: true,
            onError: () => {
                toast.add({ severity: 'error', summary: 'Replace failed', detail: file.name, life: 5000 });
                replacingUuid.value = null;
            },
            onSuccess: () => {
                const uploaded = props.images[props.images.length - 1];
                if (!uploaded || uploaded.uuid === oldImage.uuid) {
                    replacingUuid.value = null;
                    return;
                }
                router.delete(`/portfolios/${props.portfolioUuid}/gallery/${oldImage.uuid}`, {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => {
                        const withoutOldOrNew = props.images.filter(
                            (image) => image.uuid !== oldImage.uuid && image.uuid !== uploaded.uuid,
                        );
                        const reordered = [...withoutOldOrNew];
                        reordered.splice(Math.min(index, reordered.length), 0, uploaded);
                        commitOrder(reordered);
                    },
                    onError: () => {
                        toast.add({ severity: 'error', summary: 'Replace failed while removing the old image', life: 5000 });
                    },
                    onFinish: () => {
                        replacingUuid.value = null;
                        toast.add({ severity: 'success', summary: 'Image replaced', life: 3000 });
                    },
                });
            },
        },
    );
}

/* ── Reorder ─────────────────────────────────────────────────────────── */
function commitOrder(next: PortfolioGalleryImage[]): void {
    localImages.value = next;
    reordering.value = true;
    router.post(
        `/portfolios/${props.portfolioUuid}/gallery/reorder`,
        { uuids: next.map((image) => image.uuid) },
        {
            preserveScroll: true,
            preserveState: true,
            onError: () => {
                toast.add({ severity: 'error', summary: 'Reorder failed', life: 5000 });
                localImages.value = [...props.images];
            },
            onFinish: () => {
                reordering.value = false;
            },
        },
    );
}

function moveImage(index: number, delta: number): void {
    const target = index + delta;
    if (target < 0 || target >= localImages.value.length) {
        return;
    }
    const next = [...localImages.value];
    const [moved] = next.splice(index, 1);
    next.splice(target, 0, moved);
    commitOrder(next);
}

/**
 * `vuedraggable`'s `v-model` already spliced `localImages` in place by the
 * time this fires; `event.moved` is only present when the drag actually
 * changed the order (a no-op drag — picked up and dropped in the same spot —
 * must not fire a request).
 */
function onDraggableChange(event: { moved?: { newIndex: number; oldIndex: number } }): void {
    if (!event.moved) {
        return;
    }
    commitOrder([...localImages.value]);
}

/* ── Delete ──────────────────────────────────────────────────────────── */
const {
    visible: deleteVisible,
    loading: deleteLoading,
    confirm: deleteConfirm,
    ask: askDelete,
    run: runDelete,
} = useConfirmAction<PortfolioGalleryImage>(() => ({
    title: 'Remove gallery image',
    message: 'Remove this image from the gallery? This cannot be undone.',
    confirmLabel: 'Remove',
    confirmIcon: 'pi pi-trash',
    tone: 'danger',
}));

function confirmDelete(): void {
    runDelete((image, finish) => {
        router.delete(`/portfolios/${props.portfolioUuid}/gallery/${image.uuid}`, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Image removed', life: 3000 });
            },
            onFinish: finish,
        });
    });
}
</script>

<template>
    <div class="pf-gallery">
        <PermissionGuard permission="UPDATE_PORTFOLIOS">
            <div
                class="pf-gallery__drop"
                :class="{ 'pf-gallery__drop--active': dropActive, 'pf-gallery__drop--busy': uploading }"
                role="button"
                tabindex="0"
                aria-label="Add gallery images"
                @click="openPicker"
                @keydown.enter.prevent="openPicker"
                @keydown.space.prevent="openPicker"
                @dragover.prevent="dropActive = true"
                @dragleave.prevent="dropActive = false"
                @drop.prevent="onDropzoneDrop"
            >
                <input
                    ref="inputRef"
                    type="file"
                    class="pf-gallery__input"
                    accept="image/jpeg,image/png,image/webp"
                    multiple
                    @change="onInputChange"
                />
                <i class="pi pi-cloud-upload" aria-hidden="true" />
                <span>
                    <strong>Click to add images</strong> or drag and drop — JPG, PNG or WEBP, up to
                    {{ MAX_SIZE_MB }} MB each.
                </span>
                <span v-if="uploading" class="pf-gallery__status">Uploading…</span>
            </div>

            <!-- Shared hidden input for the per-thumbnail "Replace" action below. -->
            <input
                ref="replaceInputRef"
                type="file"
                class="pf-gallery__input"
                accept="image/jpeg,image/png,image/webp"
                @change="onReplaceInputChange"
            />
        </PermissionGuard>

        <p v-if="localImages.length === 0" class="pf-gallery__empty">No gallery images yet.</p>

        <draggable
            v-else
            v-model="localImages"
            item-key="uuid"
            tag="div"
            class="pf-gallery__grid"
            :class="{ 'pf-gallery__grid--busy': reordering }"
            :disabled="replacingUuid !== null"
            :animation="150"
            ghost-class="pf-gallery__item--ghost"
            drag-class="pf-gallery__item--dragging"
            @change="onDraggableChange"
        >
            <template #item="{ element: image, index }: { element: PortfolioGalleryImage; index: number }">
                <div
                    class="pf-gallery__item"
                    :class="{ 'pf-gallery__item--busy': replacingUuid === image.uuid }"
                >
                    <Image
                        v-if="image.url"
                        :src="image.url"
                        :alt="`Gallery image ${index + 1}`"
                        preview
                        :pt="imagePreviewPt"
                        image-class="pf-gallery__thumb"
                    />
                    <span class="pf-gallery__position">{{ index + 1 }}</span>
                    <span v-if="replacingUuid === image.uuid" class="pf-gallery__busy-label">Replacing…</span>

                    <PermissionGuard permission="UPDATE_PORTFOLIOS">
                        <div class="pf-gallery__controls">
                            <button
                                type="button"
                                class="pf-gallery__ctrl"
                                :disabled="index === 0 || replacingUuid !== null"
                                aria-label="Move image earlier"
                                v-tooltip.top="'Move left'"
                                @click="moveImage(index, -1)"
                            >
                                <i class="pi pi-arrow-left" aria-hidden="true" />
                            </button>
                            <button
                                type="button"
                                class="pf-gallery__ctrl"
                                :disabled="replacingUuid !== null"
                                aria-label="Replace image"
                                v-tooltip.top="'Replace'"
                                @click="openReplacePicker(index, image)"
                            >
                                <i class="pi pi-refresh" aria-hidden="true" />
                            </button>
                            <button
                                type="button"
                                class="pf-gallery__ctrl pf-gallery__ctrl--danger"
                                :disabled="replacingUuid !== null"
                                aria-label="Remove image"
                                v-tooltip.top="'Remove'"
                                @click="askDelete(image)"
                            >
                                <i class="pi pi-trash" aria-hidden="true" />
                            </button>
                            <button
                                type="button"
                                class="pf-gallery__ctrl"
                                :disabled="index === localImages.length - 1 || replacingUuid !== null"
                                aria-label="Move image later"
                                v-tooltip.top="'Move right'"
                                @click="moveImage(index, 1)"
                            >
                                <i class="pi pi-arrow-right" aria-hidden="true" />
                            </button>
                        </div>
                    </PermissionGuard>
                </div>
            </template>
        </draggable>

        <ConfirmDialog
            v-model:visible="deleteVisible"
            :title="deleteConfirm.title"
            :message="deleteConfirm.message"
            :confirm-label="deleteConfirm.confirmLabel"
            :confirm-icon="deleteConfirm.confirmIcon"
            :tone="deleteConfirm.tone"
            :loading="deleteLoading"
            @confirm="confirmDelete"
        />
    </div>
</template>

<style scoped>
.pf-gallery {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
}

.pf-gallery__drop {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-5);
    text-align: center;
    border: 1px dashed var(--border-default);
    border-radius: var(--radius-md);
    background: var(--bg-surface);
    color: var(--text-muted);
    font-size: var(--text-sm);
    cursor: pointer;
    transition: border-color var(--transition), background var(--transition);
}

.pf-gallery__drop:hover,
.pf-gallery__drop:focus-visible {
    border-color: var(--accent-primary);
    background: color-mix(in srgb, var(--accent-primary) 5%, var(--bg-surface));
}

.pf-gallery__drop--active {
    border-color: var(--accent-primary);
    background: color-mix(in srgb, var(--accent-primary) 10%, var(--bg-surface));
}

.pf-gallery__drop--busy {
    opacity: 0.7;
    pointer-events: none;
}

.pf-gallery__drop .pi {
    font-size: var(--text-2xl);
    color: var(--accent-primary);
}

.pf-gallery__drop strong {
    color: var(--text-secondary);
}

.pf-gallery__status {
    font-size: var(--text-xs);
    color: var(--accent-primary);
}

.pf-gallery__input {
    display: none;
}

.pf-gallery__empty {
    margin: 0;
    font-size: var(--text-sm);
    color: var(--text-muted);
}

.pf-gallery__grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(9rem, 1fr));
    gap: var(--space-3);
    transition: opacity var(--transition);
}

.pf-gallery__grid--busy {
    opacity: 0.6;
    pointer-events: none;
}

.pf-gallery__item {
    position: relative;
    aspect-ratio: 1 / 1;
    border-radius: var(--radius-md);
    border: 1px solid var(--border-subtle);
    background: var(--bg-elevated);
    overflow: hidden;
    cursor: grab;
    transition: border-color var(--transition), transform var(--transition);
}

.pf-gallery__item:active {
    cursor: grabbing;
}

.pf-gallery__item--ghost {
    border-color: var(--accent-primary);
    opacity: 0.4;
}

.pf-gallery__item--dragging {
    border-color: var(--accent-primary);
    transform: scale(1.02);
    box-shadow: 0 8px 20px color-mix(in srgb, var(--accent-primary) 25%, transparent);
}

.pf-gallery__item--busy {
    cursor: default;
}

.pf-gallery__item :deep(.pf-gallery__thumb) {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.pf-gallery__position {
    position: absolute;
    top: var(--space-2);
    left: var(--space-2);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 1.25rem;
    height: 1.25rem;
    padding-inline: 4px;
    border-radius: 999px;
    background: var(--photo-scrim);
    color: var(--on-photo);
    font-size: var(--text-xs);
    font-weight: var(--font-semibold);
    pointer-events: none;
}

.pf-gallery__busy-label {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--photo-scrim-strong);
    color: var(--on-photo);
    font-size: var(--text-xs);
    font-weight: var(--font-semibold);
    pointer-events: none;
}

.pf-gallery__controls {
    position: absolute;
    inset-inline: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-1);
    padding: var(--space-1);
    background: linear-gradient(to top, var(--photo-scrim-strong), transparent);
    opacity: 0;
    transition: opacity var(--transition);
}

.pf-gallery__item:hover .pf-gallery__controls,
.pf-gallery__item:focus-within .pf-gallery__controls {
    opacity: 1;
}

.pf-gallery__ctrl {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.75rem;
    height: 1.75rem;
    border: none;
    border-radius: var(--radius-sm);
    background: var(--on-photo-hover-bg);
    color: var(--on-photo);
    cursor: pointer;
    transition: background var(--transition);
}

.pf-gallery__ctrl:hover:not(:disabled) {
    background: color-mix(in srgb, var(--on-photo) 28%, transparent);
}

.pf-gallery__ctrl:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.pf-gallery__ctrl--danger:hover:not(:disabled) {
    background: var(--accent-error);
}

.pf-gallery__ctrl:focus-visible {
    outline: 2px solid var(--accent-primary);
    outline-offset: 2px;
}
</style>
